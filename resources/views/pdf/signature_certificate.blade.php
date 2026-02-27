<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Certificat de signature — {{ $document['name'] }}</title>
<style>
  /* ── Typographie ──────────────────────────────────────────────────────── */
  @font-face {
    font-family: 'DejaVu Sans';
    font-style: normal;
    font-weight: normal;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 9pt;
    color: #1E293B;
    background: #fff;
    line-height: 1.5;
  }

  /* ── En-tête ─────────────────────────────────────────────────────────── */
  .header {
    background: linear-gradient(135deg, #1B4F72 0%, #2E86C1 100%);
    color: white;
    padding: 24px 32px;
    position: relative;
    overflow: hidden;
  }

  .header-brand {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
  }

  .header-logo-box {
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    padding: 6px;
  }

  .header-brand-name {
    font-size: 15pt;
    font-weight: bold;
    letter-spacing: 0.5px;
  }

  .header-brand-sub {
    font-size: 7.5pt;
    opacity: 0.8;
    margin-top: 1px;
  }

  .header-title {
    font-size: 18pt;
    font-weight: bold;
    letter-spacing: 0.3px;
    margin-top: 4px;
  }

  .header-subtitle {
    font-size: 8.5pt;
    opacity: 0.85;
    margin-top: 3px;
  }

  .cert-id-badge {
    position: absolute;
    top: 24px;
    right: 32px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 7pt;
    text-align: right;
  }

  .cert-id-label { opacity: 0.8; }
  .cert-id-value { font-weight: bold; font-size: 8pt; font-family: monospace; letter-spacing: 0.5px; }

  /* ── Corps ───────────────────────────────────────────────────────────── */
  .body {
    padding: 28px 32px;
  }

  /* ── Sections ────────────────────────────────────────────────────────── */
  .section {
    margin-bottom: 20px;
  }

  .section-title {
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #64748B;
    border-bottom: 1px solid #E2E8F0;
    padding-bottom: 5px;
    margin-bottom: 12px;
  }

  /* ── Grille 2 colonnes ───────────────────────────────────────────────── */
  .grid-2 {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
  }

  .grid-2 td {
    width: 50%;
    vertical-align: top;
    padding-right: 20px;
  }

  .grid-2 td:last-child { padding-right: 0; }

  /* ── Info blocks ─────────────────────────────────────────────────────── */
  .info-block {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 8px;
  }

  .info-block-label {
    font-size: 7pt;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
  }

  .info-block-value {
    font-size: 9pt;
    font-weight: bold;
    color: #1E293B;
  }

  .info-block-value.mono {
    font-family: monospace;
    font-size: 7.5pt;
    font-weight: normal;
    color: #475569;
    word-break: break-all;
  }

  /* ── Statut du document ──────────────────────────────────────────────── */
  .status-banner {
    background: #F0FDF4;
    border: 1.5px solid #BBF7D0;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
  }

  .status-banner-icon {
    font-size: 18pt;
    margin-right: 12px;
    margin-top: 1px;
  }

  .status-banner-title {
    font-size: 11pt;
    font-weight: bold;
    color: #15803D;
    margin-bottom: 2px;
  }

  .status-banner-text {
    font-size: 7.5pt;
    color: #166534;
  }

  /* ── Tableau des signatures ──────────────────────────────────────────── */
  .sig-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8pt;
  }

  .sig-table th {
    background: #1B4F72;
    color: white;
    font-size: 7pt;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 10px;
    text-align: left;
    font-weight: bold;
  }

  .sig-table th:first-child { border-radius: 6px 0 0 0; }
  .sig-table th:last-child  { border-radius: 0 6px 0 0; }

  .sig-table td {
    padding: 9px 10px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: top;
  }

  .sig-table tr:last-child td { border-bottom: none; }

  .sig-table tr:nth-child(even) td { background: #F8FAFC; }

  .badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 20px;
    font-size: 6.5pt;
    font-weight: bold;
  }

  .badge-signed   { background: #DCFCE7; color: #15803D; }
  .badge-pending  { background: #FEF9C3; color: #854D0E; }
  .badge-declined { background: #FEE2E2; color: #DC2626; }
  .badge-expired  { background: #F1F5F9; color: #64748B; }

  .hash-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 4px;
    padding: 4px 7px;
    font-family: monospace;
    font-size: 6.5pt;
    color: #475569;
    margin-top: 4px;
    word-break: break-all;
  }

  /* ── Proof block ─────────────────────────────────────────────────────── */
  .proof-box {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-radius: 8px;
    padding: 12px 16px;
    margin-top: 8px;
  }

  .proof-box-title {
    font-size: 7pt;
    font-weight: bold;
    color: #1D4ED8;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .proof-row {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4px;
  }

  .proof-row td {
    padding: 1px 0;
    vertical-align: top;
    font-size: 7.5pt;
  }

  .proof-label {
    color: #60A5FA;
    width: 110px;
    white-space: nowrap;
  }

  .proof-value {
    color: #1E40AF;
    font-family: monospace;
    word-break: break-all;
  }

  /* ── Pied de page ────────────────────────────────────────────────────── */
  .footer {
    border-top: 1px solid #E2E8F0;
    padding: 16px 32px;
    background: #F8FAFC;
  }

  .footer-legal {
    font-size: 6.5pt;
    color: #94A3B8;
    text-align: center;
    line-height: 1.6;
  }

  .footer-legal strong { color: #64748B; }

  .footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #E2E8F0;
    font-size: 6.5pt;
    color: #94A3B8;
  }

  /* ── Watermark sceau ─────────────────────────────────────────────────── */
  .seal-watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    width: 220px;
    height: 220px;
    border: 4px solid rgba(27, 79, 114, 0.05);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 0;
  }

  .seal-text {
    font-size: 10pt;
    font-weight: bold;
    color: rgba(27, 79, 114, 0.05);
    text-transform: uppercase;
    letter-spacing: 3px;
    text-align: center;
  }

  /* ── Divider ─────────────────────────────────────────────────────────── */
  .divider {
    border: none;
    border-top: 1px solid #E2E8F0;
    margin: 20px 0;
  }

  /* ── Utilitaires ─────────────────────────────────────────────────────── */
  .text-muted { color: #94A3B8; }
  .text-small { font-size: 7.5pt; }
  .mt-4 { margin-top: 4px; }
  .mt-8 { margin-top: 8px; }
  .fw-bold { font-weight: bold; }
</style>
</head>
<body>

{{-- Filigrane sceau (visible en arrière-plan) --}}
<div class="seal-watermark">
  <div class="seal-text">SCELLÉ<br/>SIMPLI<br/>RH</div>
</div>

<!-- ── En-tête ──────────────────────────────────────────────────────────── -->
<div class="header">
  <div class="header-brand">
    <div class="header-logo-box">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"
              stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <div>
      <div class="header-brand-name">SimpliRH</div>
      <div class="header-brand-sub">Plateforme RH · Signature électronique</div>
    </div>
  </div>
  <div class="header-title">Certificat de Signature Électronique</div>
  <div class="header-subtitle">
    Document officiel de preuve — Généré le {{ $generated_at }}
  </div>

  <!-- ID certificat -->
  <div class="cert-id-badge">
    <div class="cert-id-label">N° certificat</div>
    <div class="cert-id-value">{{ $certificate_id }}</div>
  </div>
</div>

<!-- ── Corps ────────────────────────────────────────────────────────────── -->
<div class="body">

  <!-- Bannière statut global -->
  <div class="status-banner">
    <div class="status-banner-icon">✅</div>
    <div>
      <div class="status-banner-title">
        @if($document['signature_status'] === 'completed')
          Document intégralement signé et scellé
        @elseif($document['signature_status'] === 'partial')
          Document partiellement signé
        @else
          Certificat de suivi des signatures
        @endif
      </div>
      <div class="status-banner-text">
        {{ $signed_count }} signature(s) collectée(s)
        @if($total_signatures > 0) sur {{ $total_signatures }} demandée(s)@endif.
        @if($document['signature_status'] === 'completed')
          L'intégrité du document est garantie par l'empreinte SHA-256.
        @endif
      </div>
    </div>
  </div>

  <!-- ── Informations document ─────────────────────────────────────────── -->
  <div class="section">
    <div class="section-title">Informations du document</div>
    <table class="grid-2">
      <tr>
        <td>
          <div class="info-block">
            <div class="info-block-label">Nom du document</div>
            <div class="info-block-value">{{ $document['name'] }}</div>
          </div>
          <div class="info-block">
            <div class="info-block-label">Catégorie</div>
            <div class="info-block-value">{{ $document['category_label'] }}</div>
          </div>
          <div class="info-block">
            <div class="info-block-label">Fichier original</div>
            <div class="info-block-value">{{ $document['original_filename'] }}</div>
          </div>
        </td>
        <td>
          <div class="info-block">
            <div class="info-block-label">Taille</div>
            <div class="info-block-value">{{ $document['file_size_label'] }}</div>
          </div>
          <div class="info-block">
            <div class="info-block-label">Date d'import</div>
            <div class="info-block-value">{{ $document['created_at'] }}</div>
          </div>
          <div class="info-block">
            <div class="info-block-label">Importé par</div>
            <div class="info-block-value">{{ $document['uploaded_by_name'] ?? '—' }}</div>
          </div>
        </td>
      </tr>
    </table>
  </div>

  <!-- ── Informations entreprise ───────────────────────────────────────── -->
  <div class="section">
    <div class="section-title">Entreprise</div>
    <table class="grid-2">
      <tr>
        <td>
          <div class="info-block">
            <div class="info-block-label">Société</div>
            <div class="info-block-value">{{ $company['name'] }}</div>
          </div>
        </td>
        <td>
          @if($document['user_name'])
          <div class="info-block">
            <div class="info-block-label">Signataire désigné</div>
            <div class="info-block-value">{{ $document['user_name'] }}</div>
          </div>
          @endif
        </td>
      </tr>
    </table>
  </div>

  <!-- ── Signatures ────────────────────────────────────────────────────── -->
  <div class="section">
    <div class="section-title">Détail des signatures ({{ count($signatures) }})</div>

    @if(empty($signatures))
      <div style="text-align:center; padding: 20px; color: #94A3B8; font-size: 8pt;">
        Aucune signature enregistrée pour ce document.
      </div>
    @else
      <table class="sig-table">
        <thead>
          <tr>
            <th style="width:25%">Signataire</th>
            <th style="width:12%">Statut</th>
            <th style="width:22%">Date</th>
            <th style="width:18%">Adresse IP</th>
            <th style="width:23%">Type</th>
          </tr>
        </thead>
        <tbody>
          @foreach($signatures as $sig)
          <tr>
            <td>
              <div class="fw-bold">{{ $sig['user_name'] ?? '—' }}</div>
              <div class="text-muted text-small">{{ $sig['user_email'] ?? '' }}</div>
            </td>
            <td>
              <span class="badge badge-{{ $sig['status'] }}">
                {{ $sig['status_label'] }}
              </span>
            </td>
            <td>
              @if($sig['status'] === 'signed' && $sig['signed_at'])
                <div>{{ $sig['signed_at'] }}</div>
              @elseif($sig['status'] === 'declined' && $sig['declined_at'])
                <div class="text-muted">Refusé le</div>
                <div>{{ $sig['declined_at'] }}</div>
              @elseif($sig['status'] === 'pending')
                <div class="text-muted">En attente</div>
                <div class="text-small">Expire le {{ $sig['expires_at'] }}</div>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td>
              @if($sig['ip_address'])
                <span style="font-family: monospace; font-size: 7.5pt;">{{ $sig['ip_address'] }}</span>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td>
              @if($sig['signature_type'] === 'drawn')
                ✍ Manuscrite (dessinée)
              @elseif($sig['signature_type'] === 'typed')
                ⌨ Dactylographiée
                @if($sig['typed_name'])
                  <div class="text-muted text-small">« {{ $sig['typed_name'] }} »</div>
                @endif
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
          </tr>
          @if($sig['status'] === 'signed' && !empty($sig['document_hash']))
          <tr>
            <td colspan="5" style="padding: 4px 10px 10px;">
              <div class="hash-box">
                Empreinte SHA-256 du document au moment de la signature :
                <br/>{{ $sig['document_hash'] }}
              </div>
            </td>
          </tr>
          @endif
          @if($sig['status'] === 'declined' && $sig['declined_reason'])
          <tr>
            <td colspan="5" style="padding: 4px 10px 10px;">
              <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:6px 10px; font-size:7.5pt; color:#DC2626;">
                Motif de refus : {{ $sig['declined_reason'] }}
              </div>
            </td>
          </tr>
          @endif
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  <!-- ── Preuve d'intégrité ─────────────────────────────────────────────── -->
  @if($document['signature_status'] === 'completed')
  <div class="section">
    <div class="section-title">Preuve d'intégrité</div>
    <div class="proof-box">
      <div class="proof-box-title">Données de vérification cryptographique</div>
      <table class="proof-row">
        <tr>
          <td class="proof-label">Algorithme :</td>
          <td class="proof-value">SHA-256 (NIST FIPS 180-4)</td>
        </tr>
        <tr>
          <td class="proof-label">Chiffrement :</td>
          <td class="proof-value">AES-256-CBC (NIST FIPS 197)</td>
        </tr>
        <tr>
          <td class="proof-label">Certificat ID :</td>
          <td class="proof-value">{{ $certificate_id }}</td>
        </tr>
        <tr>
          <td class="proof-label">Généré le :</td>
          <td class="proof-value">{{ $generated_at_full }}</td>
        </tr>
        @if(!empty($first_signature_hash))
        <tr>
          <td class="proof-label">Hash document :</td>
          <td class="proof-value">{{ $first_signature_hash }}</td>
        </tr>
        @endif
      </table>
    </div>
  </div>
  @endif

</div>

<!-- ── Pied de page ──────────────────────────────────────────────────────── -->
<div class="footer">
  <div class="footer-legal">
    Ce certificat atteste de la réalité et de l'intégrité des signatures électroniques apposées sur le document
    <strong>« {{ $document['name'] }} »</strong>. Conformément au règlement eIDAS (Règlement UE n°910/2014)
    et à l'article 1367 du Code civil français, les signatures électroniques consignées dans ce document ont la même
    valeur juridique qu'une signature manuscrite. Les données de preuve (adresse IP, horodatage, empreinte SHA-256)
    sont conservées par <strong>SimpliRH</strong> pour garantir la non-répudiation.
  </div>
  <div class="footer-bottom">
    <span>SimpliRH — Gestion RH · simplirh.fr</span>
    <span>Certificat n° <strong>{{ $certificate_id }}</strong></span>
    <span>Généré le {{ $generated_at }}</span>
  </div>
</div>

</body>
</html>
