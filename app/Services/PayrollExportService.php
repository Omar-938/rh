<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\OvertimeStatus;
use App\Enums\PayrollExportStatus;
use App\Jobs\SendPayrollExportJob;
use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\OvertimeEntry;
use App\Models\PayrollExport;
use App\Models\PayrollExportLine;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PayrollExportService
{
    // ─── Génération / création ───────────────────────────────────────────────────

    /**
     * Crée (ou récupère) un export brouillon pour la période donnée.
     *
     * @param  string  $period  Format "YYYY-MM"
     */
    public function findOrCreate(Company $company, string $period): PayrollExport
    {
        return PayrollExport::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('period', $period)
            ->firstOr(fn () => PayrollExport::create([
                'company_id' => $company->id,
                'period'     => $period,
                'status'     => PayrollExportStatus::Draft,
                'format'     => $company->settings['payroll_export_format'] ?? 'pdf',
            ]));
    }

    // ─── Compilation ────────────────────────────────────────────────────────────

    /**
     * Compile les variables mensuelles de tous les employés actifs.
     * Supprime et régénère les lignes existantes (idempotent sur les brouillons).
     */
    public function compile(PayrollExport $export): void
    {
        abort_unless($export->canBeModified(), 422, 'Seul un brouillon peut être recompilé.');

        $period     = Carbon::createFromFormat('Y-m', $export->period);
        $monthStart = $period->copy()->startOfMonth();
        $monthEnd   = $period->copy()->endOfMonth();
        $year       = $period->year;
        $month      = $period->month;

        $workingDays = $this->countWorkingDays($monthStart, $monthEnd);

        $employees = User::withoutGlobalScopes()
            ->where('company_id', $export->company_id)
            ->where('is_active', true)
            ->with('department')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        DB::transaction(function () use ($export, $employees, $monthStart, $monthEnd, $workingDays, $year, $month): void {
            // Suppression des lignes existantes (re-génération complète)
            $export->lines()->delete();

            foreach ($employees as $employee) {
                $data = $this->compileEmployee(
                    $employee, $export->company_id,
                    $monthStart, $monthEnd,
                    $workingDays, $year, $month,
                );

                PayrollExportLine::create([
                    'payroll_export_id' => $export->id,
                    'user_id'           => $employee->id,
                    'data'              => $data,
                    'is_modified'       => false,
                ]);
            }

            $export->touch();
        });
    }

    /**
     * Compile les données pour un seul employé.
     */
    private function compileEmployee(
        User   $employee,
        int    $companyId,
        Carbon $monthStart,
        Carbon $monthEnd,
        int    $workingDays,
        int    $year,
        int    $month,
    ): array {
        // ── Congés approuvés chevauchant le mois ───────────────────────────────
        $leaves = LeaveRequest::withoutGlobalScopes()
            ->where('user_id', $employee->id)
            ->where('company_id', $companyId)
            ->where('status', LeaveStatus::Approved->value)
            ->where('start_date', '<=', $monthEnd->toDateString())
            ->where('end_date', '>=', $monthStart->toDateString())
            ->with('leaveType')
            ->get();

        $absencesByType   = [];
        $totalAbsenceDays = 0.0;

        foreach ($leaves as $leave) {
            $overlapStart = $leave->start_date->max($monthStart);
            $overlapEnd   = $leave->end_date->min($monthEnd);

            $days = (float) $this->countWorkingDays($overlapStart, $overlapEnd);

            // Demi-journée début : si la date de début est dans le mois
            if ($leave->start_half === 'afternoon'
                && $leave->start_date->between($monthStart, $monthEnd)
            ) {
                $days = max(0.0, $days - 0.5);
            }

            // Demi-journée fin : si la date de fin est dans le mois
            if ($leave->end_half === 'morning'
                && $leave->end_date->between($monthStart, $monthEnd)
            ) {
                $days = max(0.0, $days - 0.5);
            }

            if ($days <= 0) {
                continue;
            }

            $typeName = $leave->leaveType->name ?? 'Congé';
            $absencesByType[$typeName] = ($absencesByType[$typeName] ?? 0.0) + $days;
            $totalAbsenceDays += $days;
        }

        $absences = array_values(
            array_map(
                fn (string $type, float $days) => ['type' => $type, 'days' => round($days, 2)],
                array_keys($absencesByType),
                $absencesByType,
            )
        );

        // ── Heures supplémentaires approuvées du mois ─────────────────────────
        $overtimes = OvertimeEntry::withoutGlobalScopes()
            ->where('user_id', $employee->id)
            ->where('company_id', $companyId)
            ->where('status', OvertimeStatus::Approved->value)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $hours25 = 0.0;
        $hours50 = 0.0;

        foreach ($overtimes as $ot) {
            if ($ot->rate === '50') {
                $hours50 += (float) $ot->hours;
            } else {
                $hours25 += (float) $ot->hours;
            }
        }

        $defaultCompensation = $overtimes->first()?->compensation ?? 'payment';

        // ── Calcul final ───────────────────────────────────────────────────────
        $daysWorked = max(0.0, $workingDays - $totalAbsenceDays);

        return [
            'employee_id'   => $employee->employee_id,
            'full_name'     => $employee->full_name,
            'department'    => $employee->department?->name,
            'contract_type' => $employee->contract_type?->value,
            'working_days'  => $workingDays,
            'days_worked'   => round($daysWorked, 2),
            'days_absent'   => round($totalAbsenceDays, 2),
            'absences'      => $absences,
            'overtime'      => [
                'hours_25'     => round($hours25, 2),
                'hours_50'     => round($hours50, 2),
                'total_hours'  => round($hours25 + $hours50, 2),
                'compensation' => $defaultCompensation,
            ],
            'variables' => [],  // Saisi manuellement par le RH (étape 31)
            'notes'     => '',
        ];
    }

    /**
     * Compte les jours ouvrés (lundi → vendredi) entre deux dates incluses.
     */
    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        if ($start->gt($end)) {
            return 0;
        }

        $days    = 0;
        $current = $start->copy()->startOfDay();
        $endDay  = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    // ─── Mise à jour ligne ───────────────────────────────────────────────────────

    /**
     * Met à jour les données d'une ligne et marque is_modified = true.
     */
    public function updateLine(PayrollExportLine $line, array $data): void
    {
        DB::transaction(function () use ($line, $data): void {
            $merged = array_merge($line->data ?? [], $data);
            $line->update(['data' => $merged, 'is_modified' => true]);
        });
    }

    // ─── Validation ─────────────────────────────────────────────────────────────

    public function validate(PayrollExport $export, User $validator): void
    {
        abort_unless($export->isDraft(), 422, 'Seul un brouillon peut être validé.');

        $export->update([
            'status'       => PayrollExportStatus::Validated,
            'validated_by' => $validator->id,
            'validated_at' => now(),
        ]);
    }

    // ─── Sérialisation ──────────────────────────────────────────────────────────

    public function serialize(PayrollExport $export): array
    {
        return [
            'id'             => $export->id,
            'period'         => $export->period,
            'period_label'   => $export->period_label,
            'period_short'   => $export->period_short,
            'period_year'    => $export->period_year,
            'period_month'   => $export->period_month,
            'status'         => $export->status->value,
            'status_label'   => $export->status->label(),
            'status_color'   => $export->status->color(),
            'can_edit'       => $export->canBeModified(),
            'is_correction'  => $export->is_correction,
            'format'         => $export->format,
            'lines_count'    => $export->lines_count ?? $export->lines()->count(),
            'validated_at'   => $export->validated_at?->translatedFormat('j M Y'),
            'validated_by'   => $export->validatedBy?->full_name,
            'sent_at'        => $export->sent_at?->translatedFormat('j M Y à H:i'),
            'sent_to'        => $export->sent_to,
            'notes'          => $export->notes,
            'show_url'       => route('payroll-exports.show', $export->id),
            'created_at'     => $export->created_at->translatedFormat('j M Y'),
        ];
    }

    public function serializeLine(PayrollExportLine $line): array
    {
        return [
            'id'            => $line->id,
            'user_id'       => $line->user_id,
            'user_name'     => $line->user?->full_name,
            'user_initials' => $line->user?->initials,
            'department'    => $line->user?->department?->name,
            'data'          => $line->data,
            'is_modified'   => $line->is_modified,
            'updated_at'    => $line->updated_at?->translatedFormat('j M Y à H:i'),
        ];
    }

    // ─── Génération de fichiers ──────────────────────────────────────────────────

    /**
     * Génère le contenu binaire du fichier dans le format demandé.
     *
     * @param  Collection<PayrollExportLine>  $lines
     */
    public function generateContent(PayrollExport $export, Collection $lines, string $format): string
    {
        return match($format) {
            'pdf'  => $this->generatePDFContent($export, $lines),
            'xlsx' => $this->generateExcelContent($export, $lines),
            'csv'  => $this->generateCSVContent($export, $lines),
            default => throw new \InvalidArgumentException("Format « {$format} » non supporté."),
        };
    }

    /**
     * Nom du fichier à télécharger.
     */
    public function getFilename(PayrollExport $export, string $format): string
    {
        $company  = $export->company ?? Company::withoutGlobalScopes()->find($export->company_id);
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $company?->name ?? 'export');

        return "export-paie-{$export->period}-{$safeName}.{$format}";
    }

    /**
     * MIME type correspondant au format.
     */
    public function getMimeType(string $format): string
    {
        return match($format) {
            'pdf'  => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv'  => 'text/csv; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }

    // ─── Envoi email comptable ───────────────────────────────────────────────────

    /**
     * Met à jour le statut "envoyé" et dispatch le job d'envoi email.
     */
    public function sendToAccountant(
        PayrollExport $export,
        array         $emails,
        string        $format,
        User          $sender,
    ): void {
        abort_unless(! $export->isDraft(), 422, 'Seul un export validé ou corrigé peut être envoyé.');

        DB::transaction(function () use ($export, $emails, $format): void {
            $export->update([
                'status'  => PayrollExportStatus::Sent,
                'sent_at' => now(),
                'sent_to' => $emails,
                'format'  => $format,
            ]);
        });

        SendPayrollExportJob::dispatch($export, $emails, $format, $sender);
    }

    // ─── Implémentations privées ─────────────────────────────────────────────────

    private function generatePDFContent(PayrollExport $export, Collection $lines): string
    {
        $export->loadMissing(['company', 'validatedBy']);
        $company = $export->company ?? Company::withoutGlobalScopes()->find($export->company_id);

        return Pdf::loadView('pdf.payroll_export', compact('export', 'lines', 'company'))
            ->setPaper('a4', 'landscape')
            ->output();
    }

    private function generateExcelContent(PayrollExport $export, Collection $lines): string
    {
        $export->loadMissing(['company']);
        $company = $export->company ?? Company::withoutGlobalScopes()->find($export->company_id);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($export->period_short, 0, 31));

        // ── En-tête feuille ──────────────────────────────────────────────────
        $sheet->setCellValue('A1', $company?->name . ' — Variables de paie ' . $export->period_label);
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->setCellValue('A2', 'Généré le ' . now()->format('d/m/Y à H:i') . ' — ' . $lines->count() . ' salarié(s)');
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('94A3B8');

        // ── Ligne titres colonnes ──────────────────────────────────────────
        $headers = ['Salarié', 'Département', 'Contrat', 'J. Ouvrés', 'J. Travaillés',
                    'J. Absences', 'Détail absences', 'HS 25%', 'HS 50%', 'Variables', 'Notes'];
        $sheet->fromArray($headers, null, 'A4');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B4F72']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A4:K4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(18);

        // ── Données ───────────────────────────────────────────────────────
        $row = 5;
        foreach ($lines as $line) {
            $data      = $line->data ?? [];
            $absences  = implode(' | ', array_map(fn ($a) => "{$a['type']}: {$a['days']}j", $data['absences'] ?? []));
            $variables = implode(' | ', array_map(
                fn ($v) => $v['label'] . (isset($v['amount']) && $v['amount'] !== null ? ': ' . $v['amount'] . '€' : ''),
                $data['variables'] ?? [],
            ));

            $sheet->fromArray([
                $line->user?->full_name ?? ($data['full_name'] ?? ''),
                $line->user?->department?->name ?? ($data['department'] ?? ''),
                strtoupper($data['contract_type'] ?? ''),
                (int) ($data['working_days'] ?? 0),
                (float) ($data['days_worked'] ?? 0),
                (float) ($data['days_absent'] ?? 0),
                $absences,
                (float) ($data['overtime']['hours_25'] ?? 0),
                (float) ($data['overtime']['hours_50'] ?? 0),
                $variables,
                $data['notes'] ?? '',
            ], null, "A{$row}");

            // Fond alternant
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:K{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            if ($line->is_modified) {
                $sheet->getStyle("A{$row}:K{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFBEB');
            }

            $row++;
        }

        // ── Ligne totaux ──────────────────────────────────────────────────
        $totalsRow = $row;
        $sheet->setCellValue("A{$totalsRow}", 'TOTAUX — ' . $lines->count() . ' salarié(s)');
        $sheet->mergeCells("A{$totalsRow}:D{$totalsRow}");
        $sheet->setCellValue("E{$totalsRow}", round($lines->sum(fn ($l) => (float) ($l->data['days_worked'] ?? 0)), 2));
        $sheet->setCellValue("F{$totalsRow}", round($lines->sum(fn ($l) => (float) ($l->data['days_absent'] ?? 0)), 2));
        $sheet->setCellValue("H{$totalsRow}", round($lines->sum(fn ($l) => (float) ($l->data['overtime']['hours_25'] ?? 0)), 2));
        $sheet->setCellValue("I{$totalsRow}", round($lines->sum(fn ($l) => (float) ($l->data['overtime']['hours_50'] ?? 0)), 2));
        $sheet->getStyle("A{$totalsRow}:K{$totalsRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$totalsRow}:K{$totalsRow}")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

        // ── Mise en forme finale ──────────────────────────────────────────
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setAutoFilter("A4:K{$totalsRow}");
        $sheet->freezePane('A5');

        // ── Export binaire ────────────────────────────────────────────────
        $writer  = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'payroll_xlsx_');
        $writer->save($tmpFile);
        $content = (string) file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $content;
    }

    private function generateCSVContent(PayrollExport $export, Collection $lines): string
    {
        $output = "\xEF\xBB\xBF";  // BOM UTF-8 (compatibilité Excel)

        $headers = [
            'Nom', 'Département', 'Contrat',
            'J. Ouvrés', 'J. Travaillés', 'J. Absences', 'Détail absences',
            'HS 25%', 'HS 50%', 'Variables', 'Notes',
        ];
        $output .= $this->csvRow($headers);

        foreach ($lines as $line) {
            $data      = $line->data ?? [];
            $absences  = implode(' | ', array_map(fn ($a) => "{$a['type']}: {$a['days']}j", $data['absences'] ?? []));
            $variables = implode(' | ', array_map(
                fn ($v) => $v['label'] . (isset($v['amount']) && $v['amount'] !== null ? ': ' . $v['amount'] . '€' : ''),
                $data['variables'] ?? [],
            ));

            $output .= $this->csvRow([
                $line->user?->full_name ?? ($data['full_name'] ?? ''),
                $line->user?->department?->name ?? ($data['department'] ?? ''),
                strtoupper($data['contract_type'] ?? ''),
                $data['working_days'] ?? 0,
                $data['days_worked'] ?? 0,
                $data['days_absent'] ?? 0,
                $absences,
                $data['overtime']['hours_25'] ?? 0,
                $data['overtime']['hours_50'] ?? 0,
                $variables,
                $data['notes'] ?? '',
            ]);
        }

        // Ligne totaux
        $output .= $this->csvRow([
            'TOTAUX',
            '', '',
            '',
            round($lines->sum(fn ($l) => (float) ($l->data['days_worked'] ?? 0)), 2),
            round($lines->sum(fn ($l) => (float) ($l->data['days_absent'] ?? 0)), 2),
            '',
            round($lines->sum(fn ($l) => (float) ($l->data['overtime']['hours_25'] ?? 0)), 2),
            round($lines->sum(fn ($l) => (float) ($l->data['overtime']['hours_50'] ?? 0)), 2),
            '', '',
        ]);

        return $output;
    }

    private function csvRow(array $fields): string
    {
        return implode(
            ';',
            array_map(fn ($f) => '"' . str_replace('"', '""', (string) $f) . '"', $fields),
        ) . "\r\n";
    }
}
