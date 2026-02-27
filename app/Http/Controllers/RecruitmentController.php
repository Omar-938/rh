<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CandidateStage;
use App\Enums\ContractType;
use App\Enums\JobPostingStatus;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\JobPosting;
use App\Services\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentController extends Controller
{
    public function __construct(private RecruitmentService $service) {}

    // ─── Offres ─────────────────────────────────────────────────────────────────

    /**
     * Liste des offres d'emploi.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $status = $request->query('status', 'all');

        $query = JobPosting::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->with(['department', 'createdBy'])
            ->withCount('candidates');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $postings = $query->orderByDesc('created_at')
            ->get()
            ->map(fn (JobPosting $p) => $this->service->serializePosting($p));

        $departments = Department::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total'  => JobPosting::withoutGlobalScopes()->where('company_id', $user->company_id)->count(),
            'open'   => JobPosting::withoutGlobalScopes()->where('company_id', $user->company_id)->where('status', 'open')->count(),
            'draft'  => JobPosting::withoutGlobalScopes()->where('company_id', $user->company_id)->where('status', 'draft')->count(),
            'closed' => JobPosting::withoutGlobalScopes()->where('company_id', $user->company_id)->where('status', 'closed')->count(),
        ];

        return Inertia::render('Recruitment/Index', [
            'postings'    => $postings,
            'departments' => $departments,
            'stats'       => $stats,
            'filter'      => $status,
            'contract_types' => array_map(fn (ContractType $ct) => [
                'value' => $ct->value,
                'label' => $ct->label(),
            ], ContractType::cases()),
        ]);
    }

    /**
     * Crée une nouvelle offre d'emploi.
     */
    public function storePosting(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $data = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'contract_type' => ['required', 'string', 'in:cdi,cdd,interim,stage,alternance'],
            'location'      => ['nullable', 'string', 'max:255'],
            'salary_range'  => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:5000'],
            'requirements'  => ['nullable', 'string', 'max:5000'],
            'status'        => ['required', 'string', 'in:draft,open'],
        ]);

        $posting = JobPosting::create([
            ...$data,
            'company_id' => $user->company_id,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('recruitment.postings.show', $posting->id)
            ->with('success', "Offre « {$posting->title} » créée.");
    }

    /**
     * Pipeline kanban d'une offre.
     */
    public function showPosting(Request $request, JobPosting $posting): Response
    {
        $user = $request->user();
        abort_unless($posting->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $posting->load(['department', 'createdBy']);

        $candidates = $posting->candidates()
            ->orderBy('created_at')
            ->get()
            ->map(fn (Candidate $c) => $this->service->serializeCandidate($c));

        $departments = Department::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stages = array_map(fn (CandidateStage $s) => [
            'value' => $s->value,
            'label' => $s->label(),
            'color' => $s->color(),
            'emoji' => $s->emoji(),
        ], CandidateStage::pipelineOrder());

        return Inertia::render('Recruitment/Pipeline', [
            'posting'    => $this->service->serializePosting($posting),
            'candidates' => $candidates,
            'stages'     => $stages,
            'departments'=> $departments,
        ]);
    }

    /**
     * Met à jour une offre (titre, statut, description, etc.).
     */
    public function updatePosting(Request $request, JobPosting $posting): RedirectResponse
    {
        $user = $request->user();
        abort_unless($posting->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $data = $request->validate([
            'title'         => ['sometimes', 'string', 'max:255'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'contract_type' => ['sometimes', 'string', 'in:cdi,cdd,interim,stage,alternance'],
            'location'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'salary_range'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'description'   => ['sometimes', 'nullable', 'string', 'max:5000'],
            'requirements'  => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status'        => ['sometimes', 'string', 'in:draft,open,closed'],
        ]);

        if (isset($data['status']) && $data['status'] === 'closed' && ! $posting->isClosed()) {
            $data['closed_at'] = now();
        }

        $posting->update($data);

        return back()->with('success', 'Offre mise à jour.');
    }

    // ─── Candidats ──────────────────────────────────────────────────────────────

    /**
     * Ajoute un candidat à une offre (avec CV optionnel).
     */
    public function storeCandidate(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $data = $request->validate([
            'job_posting_id' => ['required', 'integer', 'exists:job_postings,id'],
            'first_name'     => ['required', 'string', 'max:255'],
            'last_name'      => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'stage'          => ['sometimes', 'string', 'in:received,shortlisted,interview,selected,hired,rejected'],
            'notes'          => ['nullable', 'string', 'max:5000'],
            'cv'             => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],  // 5 MB
        ]);

        // Vérifier que l'offre appartient bien à la société
        $posting = JobPosting::withoutGlobalScopes()
            ->where('id', $data['job_posting_id'])
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $cvPath         = null;
        $cvOriginalName = null;

        if ($request->hasFile('cv')) {
            $cvPath         = $this->service->storeCv($request->file('cv'), $user->company_id);
            $cvOriginalName = $request->file('cv')->getClientOriginalName();
        }

        Candidate::create([
            'company_id'      => $user->company_id,
            'job_posting_id'  => $posting->id,
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'stage'           => $data['stage'] ?? 'received',
            'notes'           => $data['notes'] ?? null,
            'cv_path'         => $cvPath,
            'cv_original_name'=> $cvOriginalName,
        ]);

        return back()->with('success', "{$data['first_name']} {$data['last_name']} ajouté(e) au pipeline.");
    }

    /**
     * Déplace un candidat vers une nouvelle étape du pipeline.
     */
    public function updateStage(Request $request, Candidate $candidate): RedirectResponse
    {
        $user = $request->user();
        abort_unless($candidate->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $request->validate([
            'stage' => ['required', 'string', 'in:received,shortlisted,interview,selected,hired,rejected'],
        ]);

        $candidate->update(['stage' => $request->input('stage')]);

        return back()->with('success', "{$candidate->full_name} déplacé(e) vers « {$candidate->stage->label()} ».");
    }

    /**
     * Met à jour les informations d'un candidat (notes, rating, interview_date).
     */
    public function updateCandidate(Request $request, Candidate $candidate): RedirectResponse
    {
        $user = $request->user();
        abort_unless($candidate->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $data = $request->validate([
            'notes'          => ['sometimes', 'nullable', 'string', 'max:5000'],
            'rating'         => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'interview_date' => ['sometimes', 'nullable', 'date'],
            'phone'          => ['sometimes', 'nullable', 'string', 'max:20'],
            'cv'             => ['sometimes', 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        if ($request->hasFile('cv')) {
            // Supprime l'ancien CV si existant
            if ($candidate->cv_path) {
                $this->service->deleteCv($candidate->cv_path);
            }
            $data['cv_path']          = $this->service->storeCv($request->file('cv'), $user->company_id);
            $data['cv_original_name'] = $request->file('cv')->getClientOriginalName();
            unset($data['cv']);
        }

        $candidate->update($data);

        return back()->with('success', 'Candidat mis à jour.');
    }

    /**
     * Supprime un candidat (et son CV).
     */
    public function destroyCandidate(Request $request, Candidate $candidate): RedirectResponse
    {
        $user = $request->user();
        abort_unless($candidate->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $name = $candidate->full_name;

        if ($candidate->cv_path) {
            $this->service->deleteCv($candidate->cv_path);
        }

        $candidate->delete();

        return back()->with('success', "{$name} supprimé(e) du pipeline.");
    }

    /**
     * Téléchargement sécurisé du CV.
     */
    public function downloadCv(Request $request, Candidate $candidate): StreamedResponse
    {
        $user = $request->user();
        abort_unless($candidate->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless($candidate->hasCv(), 404);

        $content  = Storage::disk('local')->get($candidate->cv_path);
        $filename = $candidate->cv_original_name ?? 'cv.pdf';

        return response()->streamDownload(
            fn () => print($content),
            $filename,
            ['Content-Type' => 'application/octet-stream'],
        );
    }
}
