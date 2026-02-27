<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PayrollExport;
use App\Models\PayrollExportLine;
use App\Services\PayrollExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollExportController extends Controller
{
    public function __construct(private PayrollExportService $service) {}

    /**
     * Historique des exports de la société.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $year = (int) ($request->query('year', now()->year));

        $baseQuery = PayrollExport::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->withCount('lines');

        $availableYears = PayrollExport::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period')
            ->map(fn ($p) => (int) substr((string) $p, 0, 4))
            ->unique()
            ->values()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        $exports = (clone $baseQuery)
            ->where('period', 'like', "{$year}-%")
            ->with('validatedBy')
            ->orderByDesc('period')
            ->get()
            ->map(fn (PayrollExport $e) => $this->service->serialize($e));

        $currentPeriod = now()->format('Y-m');
        $currentExport = PayrollExport::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('period', $currentPeriod)
            ->withCount('lines')
            ->first();

        return Inertia::render('PayrollExport/Index', [
            'exports'         => $exports,
            'current_export'  => $currentExport ? $this->service->serialize($currentExport) : null,
            'current_period'  => $currentPeriod,
            'current_year'    => $year,
            'available_years' => $availableYears,
        ]);
    }

    /**
     * Page de révision / contrôle d'un export.
     */
    public function show(Request $request, PayrollExport $export): Response
    {
        $user = $request->user();
        abort_unless($export->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $lines = $export->lines()
            ->with(['user.department'])
            ->orderBy('id')
            ->get()
            ->map(fn ($line) => $this->service->serializeLine($line));

        // Statistiques agrégées
        $stats = $this->aggregateStats($lines->all());

        $export->loadMissing('company');
        $accountantEmails = $export->company?->settings['accountant_emails'] ?? [];

        return Inertia::render('PayrollExport/Review', [
            'export'            => $this->service->serialize($export),
            'lines'             => $lines,
            'stats'             => $stats,
            'accountant_emails' => $accountantEmails,
        ]);
    }

    /**
     * Génère ou régénère l'export brouillon pour une période.
     */
    public function generate(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $export = $this->service->findOrCreate($user->company, $request->input('period'));

        if (! $export->canBeModified()) {
            return redirect()
                ->route('payroll-exports.show', $export->id)
                ->with('info', "Cet export est déjà {$export->status->label()}.");
        }

        $this->service->compile($export);

        $count = $export->lines()->count();

        return redirect()
            ->route('payroll-exports.show', $export->id)
            ->with('success', "Export {$export->period_label} généré — {$count} employé" . ($count > 1 ? 's' : '') . " compilé" . ($count > 1 ? 's' : '') . ".");
    }

    /**
     * Recompile les données (brouillon uniquement).
     */
    public function recompile(Request $request, PayrollExport $export): RedirectResponse
    {
        $user = $request->user();
        abort_unless($export->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless($export->canBeModified(), 422);

        $this->service->compile($export);

        $count = $export->lines()->count();

        return back()->with('success', "Données recompilées — {$count} employé" . ($count > 1 ? 's' : '') . ".");
    }

    /**
     * Met à jour une ligne (modification/ajout variables, notes, jours, heures sup).
     * Brouillon uniquement.
     */
    public function updateLine(Request $request, PayrollExport $export, PayrollExportLine $line): RedirectResponse
    {
        $user = $request->user();
        abort_unless($export->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless($export->canBeModified(), 422);
        abort_unless($line->payroll_export_id === $export->id, 403);

        $request->validate([
            'days_worked'           => ['sometimes', 'numeric', 'min:0', 'max:31'],
            'days_absent'           => ['sometimes', 'numeric', 'min:0', 'max:31'],
            'hours_25'              => ['sometimes', 'numeric', 'min:0', 'max:500'],
            'hours_50'              => ['sometimes', 'numeric', 'min:0', 'max:500'],
            'notes'                 => ['sometimes', 'nullable', 'string', 'max:1000'],
            'variables'             => ['sometimes', 'array', 'max:20'],
            'variables.*.label'     => ['required_with:variables', 'string', 'max:100'],
            'variables.*.amount'    => ['nullable', 'numeric', 'min:-99999', 'max:99999'],
        ]);

        $data = [];

        if ($request->has('days_worked')) {
            $data['days_worked'] = (float) $request->input('days_worked');
        }
        if ($request->has('days_absent')) {
            $data['days_absent'] = (float) $request->input('days_absent');
        }
        if ($request->has('notes')) {
            $data['notes'] = $request->input('notes') ?? '';
        }
        if ($request->has('variables')) {
            $data['variables'] = $request->input('variables');
        }

        // Heures sup : fusion dans le sous-objet `overtime`
        if ($request->hasAny(['hours_25', 'hours_50'])) {
            $overtime = $line->data['overtime'] ?? [];
            if ($request->has('hours_25')) {
                $overtime['hours_25'] = (float) $request->input('hours_25');
            }
            if ($request->has('hours_50')) {
                $overtime['hours_50'] = (float) $request->input('hours_50');
            }
            $overtime['total_hours'] = round(
                ($overtime['hours_25'] ?? 0) + ($overtime['hours_50'] ?? 0),
                2
            );
            $data['overtime'] = $overtime;
        }

        $this->service->updateLine($line, $data);

        return back()->with('success', 'Ligne mise à jour.');
    }

    /**
     * Valide un export (verrouille les données).
     */
    public function validate(Request $request, PayrollExport $export): RedirectResponse
    {
        $user = $request->user();
        abort_unless($export->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $this->service->validate($export, $user);

        return back()->with('success', "Export {$export->period_label} validé. Vous pouvez maintenant l'envoyer au comptable.");
    }

    /**
     * Téléchargement de l'export dans le format demandé.
     */
    public function download(Request $request, PayrollExport $export, string $format): StreamedResponse
    {
        $user = $request->user();
        abort_unless($export->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless(in_array($format, ['pdf', 'xlsx', 'csv'], true), 404);

        $lines    = $export->lines()->with(['user.department'])->orderBy('id')->get();
        $content  = $this->service->generateContent($export, $lines, $format);
        $filename = $this->service->getFilename($export, $format);
        $mimeType = $this->service->getMimeType($format);

        return response()->streamDownload(
            fn () => print($content),
            $filename,
            ['Content-Type' => $mimeType],
        );
    }

    /**
     * Envoie l'export par email au(x) comptable(s).
     */
    public function send(Request $request, PayrollExport $export): RedirectResponse
    {
        $user = $request->user();
        abort_unless($export->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless(! $export->isDraft(), 422);

        $request->validate([
            'emails'   => ['required', 'array', 'min:1', 'max:10'],
            'emails.*' => ['required', 'email', 'max:255'],
            'format'   => ['sometimes', 'string', 'in:pdf,xlsx,csv'],
        ]);

        $emails = array_values(array_unique(array_map('trim', $request->input('emails'))));
        $format = $request->input('format', $export->format ?? 'pdf');

        $this->service->sendToAccountant($export, $emails, $format, $user);

        return back()->with('success', sprintf(
            'Export %s envoyé à %s.',
            $export->period_label,
            implode(', ', $emails),
        ));
    }

    // ─── Helpers privés ─────────────────────────────────────────────────────────

    private function aggregateStats(array $lines): array
    {
        $totalEmployees   = count($lines);
        $totalDaysWorked  = 0.0;
        $totalDaysAbsent  = 0.0;
        $totalHours25     = 0.0;
        $totalHours50     = 0.0;
        $totalVariables   = 0;

        foreach ($lines as $line) {
            $data = $line['data'] ?? [];
            $totalDaysWorked += (float) ($data['days_worked'] ?? 0);
            $totalDaysAbsent += (float) ($data['days_absent'] ?? 0);
            $totalHours25    += (float) ($data['overtime']['hours_25'] ?? 0);
            $totalHours50    += (float) ($data['overtime']['hours_50'] ?? 0);
            $totalVariables  += count($data['variables'] ?? []);
        }

        return [
            'employees'    => $totalEmployees,
            'days_worked'  => round($totalDaysWorked, 2),
            'days_absent'  => round($totalDaysAbsent, 2),
            'hours_25'     => round($totalHours25, 2),
            'hours_50'     => round($totalHours50, 2),
            'variables'    => $totalVariables,
        ];
    }
}
