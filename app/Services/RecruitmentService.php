<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CandidateStage;
use App\Enums\ContractType;
use App\Enums\JobPostingStatus;
use App\Models\Candidate;
use App\Models\JobPosting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecruitmentService
{
    // ─── Offres d'emploi ────────────────────────────────────────────────────────

    public function serializePosting(JobPosting $posting): array
    {
        $counts = $posting->candidates()
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        $totalCandidates = array_sum($counts);

        return [
            'id'              => $posting->id,
            'title'           => $posting->title,
            'description'     => $posting->description,
            'requirements'    => $posting->requirements,
            'contract_type'   => $posting->contract_type?->value,
            'contract_label'  => $posting->contract_type?->label(),
            'location'        => $posting->location,
            'salary_range'    => $posting->salary_range,
            'status'          => $posting->status->value,
            'status_label'    => $posting->status->label(),
            'status_color'    => $posting->status->color(),
            'department_id'   => $posting->department_id,
            'department_name' => $posting->department?->name,
            'created_by'      => $posting->createdBy?->full_name,
            'closed_at'       => $posting->closed_at?->translatedFormat('j M Y'),
            'created_at'      => $posting->created_at->translatedFormat('j M Y'),
            'candidates_count'=> $totalCandidates,
            'stage_counts'    => $counts,
            'show_url'        => route('recruitment.postings.show', $posting->id),
            'delete_url'      => route('recruitment.postings.destroy', $posting->id),
        ];
    }

    public function serializeCandidate(Candidate $candidate): array
    {
        return [
            'id'               => $candidate->id,
            'job_posting_id'   => $candidate->job_posting_id,
            'first_name'       => $candidate->first_name,
            'last_name'        => $candidate->last_name,
            'full_name'        => $candidate->full_name,
            'initials'         => $candidate->initials,
            'email'            => $candidate->email,
            'phone'            => $candidate->phone,
            'stage'            => $candidate->stage->value,
            'stage_label'      => $candidate->stage->label(),
            'stage_color'      => $candidate->stage->color(),
            'stage_emoji'      => $candidate->stage->emoji(),
            'notes'            => $candidate->notes,
            'interview_date'   => $candidate->interview_date?->format('Y-m-d\TH:i'),
            'interview_label'  => $candidate->interview_date?->translatedFormat('j M Y à H:i'),
            'rating'           => $candidate->rating,
            'has_cv'           => $candidate->hasCv(),
            'cv_original_name' => $candidate->cv_original_name,
            'cv_url'           => $candidate->hasCv()
                ? route('recruitment.candidates.cv', $candidate->id)
                : null,
            'created_at'       => $candidate->created_at->translatedFormat('j M Y'),
        ];
    }

    // ─── Upload CV ──────────────────────────────────────────────────────────────

    /**
     * Stocke le CV dans storage/app/private/cvs/{company_id}/{uuid}.ext
     * Retourne le chemin de stockage.
     */
    public function storeCv(UploadedFile $file, int $companyId): string
    {
        $ext      = $file->getClientOriginalExtension() ?: 'pdf';
        $filename = Str::uuid() . '.' . $ext;
        $path     = "cvs/{$companyId}/{$filename}";

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    /**
     * Supprime le fichier CV du stockage.
     */
    public function deleteCv(string $path): void
    {
        Storage::disk('local')->delete($path);
    }

    // ─── Statistiques pipeline ───────────────────────────────────────────────────

    /**
     * Retourne les stats globales du pipeline pour une offre.
     */
    public function pipelineStats(JobPosting $posting): array
    {
        $counts = $posting->candidates()
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        $total = array_sum($counts);

        return [
            'total'       => $total,
            'by_stage'    => array_map(fn (CandidateStage $s) => [
                'stage' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
                'emoji' => $s->emoji(),
                'count' => (int) ($counts[$s->value] ?? 0),
            ], CandidateStage::pipelineOrder()),
        ];
    }
}
