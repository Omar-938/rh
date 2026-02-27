<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Variables de paie {{ $export->period_label }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #F8FAFC; margin: 0; padding: 0; color: #1E293B; }
        .wrapper { max-width: 560px; margin: 32px auto; background: white; border-radius: 12px; border: 1px solid #E2E8F0; overflow: hidden; }
        .header { background: #1B4F72; padding: 28px 32px; }
        .header-title { color: white; font-size: 22px; font-weight: bold; margin: 0; }
        .header-sub { color: #93C5FD; font-size: 14px; margin-top: 4px; }
        .body { padding: 28px 32px; }
        .greeting { font-size: 16px; font-weight: 600; color: #1E293B; margin-bottom: 12px; }
        .text { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 16px; }
        .summary-box { background: #F1F5F9; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .summary-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #E2E8F0; font-size: 13px; }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { color: #64748B; }
        .summary-value { font-weight: 600; color: #1E293B; }
        .attachment-notice { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #1D4ED8; margin: 20px 0; }
        .footer { padding: 20px 32px; border-top: 1px solid #F1F5F9; background: #F8FAFC; font-size: 12px; color: #94A3B8; }
        .footer a { color: #2E86C1; }
        .badge { display: inline-block; background: #DBEAFE; color: #1D4ED8; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="header-title">Variables de paie</div>
            <div class="header-sub">{{ $export->period_label }} — {{ $export->company->name }}</div>
        </div>

        <div class="body">
            <p class="greeting">Bonjour,</p>

            <p class="text">
                Veuillez trouver ci-joint l'export des variables de paie pour la période
                <strong>{{ $export->period_label }}</strong> de la société <strong>{{ $export->company->name }}</strong>.
            </p>

            <div class="summary-box">
                <div class="summary-row">
                    <span class="summary-label">Période</span>
                    <span class="summary-value">{{ $export->period_label }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Société</span>
                    <span class="summary-value">{{ $export->company->name }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Nombre de salariés</span>
                    <span class="summary-value">{{ $export->lines()->count() }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Validé par</span>
                    <span class="summary-value">{{ $export->validatedBy?->full_name ?? '—' }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Date de validation</span>
                    <span class="summary-value">{{ $export->validated_at?->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Envoyé par</span>
                    <span class="summary-value">{{ $sender->full_name }} ({{ $sender->email }})</span>
                </div>
            </div>

            <div class="attachment-notice">
                📎 Le fichier <strong>{{ $filename }}</strong> est joint à cet email.
            </div>

            <p class="text">
                Ce document a été généré automatiquement par SimpliRH. Pour toute question, contactez directement
                <strong>{{ $sender->full_name }}</strong> à l'adresse <a href="mailto:{{ $sender->email }}">{{ $sender->email }}</a>.
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0">
                Cet email a été envoyé automatiquement par
                <a href="#">SimpliRH</a> — Gestion RH pour TPE/PME.
                <br>Ne pas répondre directement à cet email.
            </p>
        </div>
    </div>
</body>
</html>
