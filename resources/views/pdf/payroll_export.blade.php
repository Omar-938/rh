<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Export Variables de Paie — {{ $export->period_label }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5pt; color: #1E293B; }

        /* ── Header ── */
        .page-header { width: 100%; margin-bottom: 12pt; border-bottom: 1.5pt solid #1B4F72; padding-bottom: 7pt; }
        .company-name { font-size: 15pt; font-weight: bold; color: #1B4F72; }
        .period-label { font-size: 10pt; color: #475569; margin-top: 2pt; }
        .header-meta { text-align: right; font-size: 7.5pt; color: #94A3B8; line-height: 1.5; }
        .status-badge { font-size: 8pt; font-weight: bold; color: #1B4F72; }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background-color: #1B4F72;
            color: #FFFFFF;
            padding: 5pt 5pt;
            text-align: left;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }
        thead th.c { text-align: center; }
        tbody tr:nth-child(even) td { background-color: #F8FAFC; }
        tbody td { padding: 5pt 5pt; border-bottom: 0.5pt solid #E2E8F0; vertical-align: top; font-size: 8pt; }
        .emp-name { font-weight: bold; font-size: 8pt; }
        .emp-dept { font-size: 7pt; color: #64748B; margin-top: 1pt; }
        .c { text-align: center; }
        .num { text-align: center; font-weight: bold; }
        .hs25 { color: #2563EB; }
        .hs50 { color: #7C3AED; }
        .absence { color: #D97706; }
        .dash { color: #CBD5E1; }
        .variable-tag {
            display: inline-block; background: #EFF6FF; color: #1D4ED8;
            border: 0.4pt solid #BFDBFE; border-radius: 2pt;
            padding: 1pt 3.5pt; font-size: 7.5pt; margin: 1pt 0;
        }
        .notes-text { color: #475569; font-style: italic; font-size: 7.5pt; }
        .badge-mod { background: #FEF3C7; color: #92400E; padding: 0.5pt 3pt; border-radius: 2pt; font-size: 7pt; }
        .totals-row td {
            background-color: #E8EDF5 !important;
            font-weight: bold;
            border-top: 1.5pt solid #94A3B8;
            font-size: 8pt;
        }

        /* ── Footer ── */
        .page-footer { margin-top: 10pt; border-top: 0.5pt solid #E2E8F0; padding-top: 5pt; font-size: 7pt; color: #94A3B8; }
        .fl { float: left; }
        .fr { float: right; }
        .cb { clear: both; }
    </style>
</head>
<body>

    <!-- En-tête -->
    <table class="page-header" style="margin-bottom: 12pt; width: 100%;">
        <tr>
            <td style="vertical-align: bottom; padding-bottom: 7pt;">
                <div class="company-name">{{ $company->name }}</div>
                <div class="period-label">Variables de paie — {{ $export->period_label }}</div>
            </td>
            <td class="header-meta" style="text-align: right; vertical-align: top; padding-bottom: 7pt; width: 32%;">
                <div class="status-badge">{{ strtoupper($export->status->label()) }}</div>
                <div>Généré le {{ now()->format('d/m/Y à H:i') }}</div>
                <div>{{ $lines->count() }} salarié(s)</div>
                @if($export->validated_at)
                <div>Validé le {{ $export->validated_at->format('d/m/Y') }}
                    @if($export->validatedBy) par {{ $export->validatedBy->full_name }}@endif
                </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Tableau principal -->
    <table>
        <thead>
            <tr>
                <th style="width:18%">Salarié</th>
                <th class="c" style="width:6%">Contrat</th>
                <th class="c" style="width:7%">J. Ouvrés</th>
                <th class="c" style="width:8%">J. Travaillés</th>
                <th class="c" style="width:7%">J. Absences</th>
                <th style="width:13%">Détail absences</th>
                <th class="c" style="width:7%">HS 25%</th>
                <th class="c" style="width:7%">HS 50%</th>
                <th style="width:15%">Variables</th>
                <th style="width:12%">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            @php
                $data    = $line->data ?? [];
                $absent  = floatval($data['days_absent'] ?? 0);
                $hs25    = floatval($data['overtime']['hours_25'] ?? 0);
                $hs50    = floatval($data['overtime']['hours_50'] ?? 0);
            @endphp
            <tr>
                <td>
                    <div class="emp-name">{{ $line->user?->full_name ?? ($data['full_name'] ?? '—') }}</div>
                    @php $dept = $line->user?->department?->name ?? ($data['department'] ?? null); @endphp
                    @if($dept)<div class="emp-dept">{{ $dept }}</div>@endif
                </td>
                <td class="c" style="font-size:7pt;">{{ strtoupper($data['contract_type'] ?? '—') }}</td>
                <td class="c">{{ $data['working_days'] ?? '—' }}</td>
                <td class="num">{{ $data['days_worked'] ?? '—' }}</td>
                <td class="num @if($absent > 0) absence @else dash @endif">
                    {{ $absent > 0 ? number_format($absent, 2, ',', '').' j' : '—' }}
                </td>
                <td style="font-size:7.5pt;">
                    @foreach($data['absences'] ?? [] as $abs)
                    <div>{{ $abs['type'] }} : <strong>{{ $abs['days'] }} j</strong></div>
                    @endforeach
                </td>
                <td class="num @if($hs25 > 0) hs25 @else dash @endif">
                    {{ $hs25 > 0 ? $hs25.'h' : '—' }}
                </td>
                <td class="num @if($hs50 > 0) hs50 @else dash @endif">
                    {{ $hs50 > 0 ? $hs50.'h' : '—' }}
                </td>
                <td>
                    @foreach($data['variables'] ?? [] as $var)
                    <span class="variable-tag">{{ $var['label'] }}@if(isset($var['amount']) && $var['amount'] !== null) : <strong>{{ $var['amount'] > 0 ? '+' : '' }}{{ $var['amount'] }}€</strong>@endif</span>
                    @endforeach
                </td>
                <td>
                    <span class="notes-text">{{ $data['notes'] ?? '' }}</span>
                    @if($line->is_modified) &nbsp;<span class="badge-mod">modifié</span>@endif
                </td>
            </tr>
            @endforeach

            <!-- Ligne totaux -->
            @php
                $totalWorked  = round($lines->sum(fn($l) => floatval($l->data['days_worked'] ?? 0)), 2);
                $totalAbsent  = round($lines->sum(fn($l) => floatval($l->data['days_absent'] ?? 0)), 2);
                $totalHs25    = round($lines->sum(fn($l) => floatval($l->data['overtime']['hours_25'] ?? 0)), 2);
                $totalHs50    = round($lines->sum(fn($l) => floatval($l->data['overtime']['hours_50'] ?? 0)), 2);
            @endphp
            <tr class="totals-row">
                <td colspan="3">TOTAUX — {{ $lines->count() }} salarié(s)</td>
                <td class="num">{{ $totalWorked }}</td>
                <td class="num @if($totalAbsent > 0) absence @else dash @endif">
                    {{ $totalAbsent > 0 ? $totalAbsent.' j' : '—' }}
                </td>
                <td></td>
                <td class="num @if($totalHs25 > 0) hs25 @else dash @endif">
                    {{ $totalHs25 > 0 ? $totalHs25.'h' : '—' }}
                </td>
                <td class="num @if($totalHs50 > 0) hs50 @else dash @endif">
                    {{ $totalHs50 > 0 ? $totalHs50.'h' : '—' }}
                </td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Pied de page -->
    <div class="page-footer">
        <div class="fl">SimpliRH — Export variables de paie — {{ $export->period_label }} — {{ $company->name }}</div>
        @if($company->siret)
        <div class="fr">SIRET : {{ $company->siret }}</div>
        @endif
        <div class="cb"></div>
    </div>

</body>
</html>
