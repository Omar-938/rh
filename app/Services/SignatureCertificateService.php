<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Signature;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignatureCertificateService
{
    /**
     * Génère et retourne le certificat PDF comme téléchargement streamé.
     */
    public function download(Document $document): StreamedResponse
    {
        $data = $this->buildData($document);
        $pdf  = $this->generatePdf($data);

        $filename = $this->buildFilename($document);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Compile toutes les données nécessaires à la vue PDF.
     */
    private function buildData(Document $document): array
    {
        $document->loadMissing(['user', 'uploadedBy.company', 'signatures.user']);

        $signatures    = $document->signatures->sortBy('created_at');
        $signedCount   = $signatures->where('status', 'signed')->count();
        $certId        = $this->buildCertificateId($document);
        $now           = now();

        // Hash du premier signataire (pour la preuve d'intégrité globale)
        $firstHash = $signatures
            ->where('status', 'signed')
            ->first()
            ?->document_hash;

        return [
            'certificate_id'      => $certId,
            'generated_at'        => $now->translatedFormat('j M Y à H:i'),
            'generated_at_full'   => $now->toIso8601String(),
            'signed_count'        => $signedCount,
            'total_signatures'    => $signatures->count(),
            'first_signature_hash'=> $firstHash,

            'document' => [
                'id'               => $document->id,
                'name'             => $document->name,
                'original_filename'=> $document->original_filename,
                'category_label'   => $document->category->label(),
                'file_size_label'  => $document->file_size_label,
                'signature_status' => $document->signature_status,
                'created_at'       => $document->created_at->translatedFormat('j M Y'),
                'uploaded_by_name' => $document->uploadedBy?->full_name,
                'user_name'        => $document->user?->full_name,
            ],

            'company' => [
                'name' => $document->uploadedBy?->company?->name ?? config('app.name'),
            ],

            'signatures' => $signatures->map(fn (Signature $s) => [
                'user_name'      => $s->user?->full_name,
                'user_email'     => $s->user?->email,
                'status'         => $s->status->value,
                'status_label'   => $s->status->label(),
                'signature_type' => $s->signature_type,
                'typed_name'     => $s->signature_type === 'typed' ? $s->signature_data : null,
                'signed_at'      => $s->signed_at?->translatedFormat('j M Y à H:i'),
                'declined_at'    => $s->declined_at?->translatedFormat('j M Y à H:i'),
                'declined_reason'=> $s->declined_reason,
                'expires_at'     => $s->expires_at?->translatedFormat('j M Y'),
                'ip_address'     => $s->ip_address,
                'document_hash'  => $s->document_hash, // Hash complet dans le PDF
            ])->values()->toArray(),
        ];
    }

    /**
     * Rend la vue Blade en PDF via DomPDF.
     */
    private function generatePdf(array $data): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.signature_certificate', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', false)
            ->setOption('dpi', 150)
            ->setOption('defaultMediaType', 'print');
    }

    /**
     * Construit un identifiant de certificat unique et reproductible.
     * Format : CERT-{doc_id}-{année}-{hash court}
     */
    private function buildCertificateId(Document $document): string
    {
        $hash = strtoupper(substr(
            hash('sha256', $document->id . $document->created_at . config('app.key')),
            0,
            8
        ));

        return sprintf('CERT-%05d-%s-%s', $document->id, now()->year, $hash);
    }

    /**
     * Nom du fichier PDF téléchargé.
     */
    private function buildFilename(Document $document): string
    {
        $slug = Str::slug($document->name, '-');
        $slug = substr($slug, 0, 60);

        return "certificat-signature_{$slug}_" . now()->format('Ymd') . '.pdf';
    }
}
