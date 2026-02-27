<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SignatureStatus;
use App\Models\Document;
use App\Models\Signature;
use App\Models\User;
use App\Notifications\DocumentSigned;
use App\Notifications\SignatureRequested;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignatureService
{
    /** Durée de validité du lien de signature (jours). */
    private const LINK_TTL_DAYS = 30;

    // ─── Demande de signature ──────────────────────────────────────────────────

    /**
     * Crée une demande de signature pour l'employé désigné du document.
     * Si une demande pending existe déjà, retourne-la (idempotent).
     */
    public function requestSignature(Document $document, User $requester): Signature
    {
        abort_unless($document->user_id !== null, 422, 'Impossible de demander une signature sur un document d\'entreprise sans destinataire.');
        abort_unless($document->requires_signature, 422, 'Ce document ne nécessite pas de signature.');

        $signer = User::withoutGlobalScopes()->findOrFail($document->user_id);

        // Idempotence : réutilise la demande pending si elle existe et n'a pas expiré
        $existing = Signature::where('document_id', $document->id)
            ->where('user_id', $signer->id)
            ->where('status', SignatureStatus::Pending->value)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $existing;
        }

        $signature = Signature::create([
            'document_id' => $document->id,
            'user_id'     => $signer->id,
            'token'       => Str::random(64),
            'status'      => SignatureStatus::Pending,
            'expires_at'  => now()->addDays(self::LINK_TTL_DAYS),
        ]);

        $document->update(['signature_status' => 'pending']);

        $signer->notify(new SignatureRequested($signature, $requester));

        return $signature;
    }

    /**
     * Révoque les demandes pending et en crée une nouvelle (re-envoi).
     */
    public function resend(Document $document, User $requester): Signature
    {
        // Expirer les demandes pending existantes
        Signature::where('document_id', $document->id)
            ->where('status', SignatureStatus::Pending->value)
            ->update(['status' => SignatureStatus::Expired->value]);

        // Créer et envoyer une nouvelle demande
        $signer = User::withoutGlobalScopes()->findOrFail($document->user_id);

        $signature = Signature::create([
            'document_id' => $document->id,
            'user_id'     => $signer->id,
            'token'       => Str::random(64),
            'status'      => SignatureStatus::Pending,
            'expires_at'  => now()->addDays(self::LINK_TTL_DAYS),
        ]);

        $document->update(['signature_status' => 'pending']);

        $signer->notify(new SignatureRequested($signature, $requester));

        return $signature;
    }

    /**
     * Révoque (annule) une demande de signature pending.
     */
    public function revoke(Document $document): void
    {
        Signature::where('document_id', $document->id)
            ->where('status', SignatureStatus::Pending->value)
            ->update(['status' => SignatureStatus::Expired->value]);

        $this->updateDocumentSignatureStatus($document->refresh());
    }

    // ─── Page de signature (publique, par token) ──────────────────────────────

    /**
     * Retourne les données pour la page de signature publique.
     */
    public function getSigningData(string $token): array
    {
        $signature = Signature::with(['document.uploadedBy.company', 'user'])
            ->where('token', $token)
            ->firstOrFail();

        // Marquer comme expiré si nécessaire
        if ($signature->isPending() && $signature->isExpired()) {
            $signature->update(['status' => SignatureStatus::Expired]);
            $signature->refresh();
        }

        return [
            'signature' => $this->serializeSignature($signature),
            'document'  => [
                'id'             => $signature->document->id,
                'name'           => $signature->document->name,
                'category'       => $signature->document->category->label(),
                'mime_type'      => $signature->document->mime_type,
                'file_size_label'=> $signature->document->file_size_label,
                'uploaded_by'    => $signature->document->uploadedBy?->full_name ?? 'SimpliRH',
                'company_name'   => $signature->document->uploadedBy?->company?->name ?? '',
            ],
            'signer' => [
                'id'       => $signature->user->id,
                'name'     => $signature->user->full_name,
                'email'    => $signature->user->email,
                'initials' => $signature->user->initials,
            ],
        ];
    }

    // ─── Enregistrement de la signature ───────────────────────────────────────

    /**
     * Enregistre la signature et scelle le document si toutes les signatures sont collectées.
     */
    public function sign(Signature $signature, string $type, string $data, string $ip, string $userAgent): void
    {
        abort_unless($signature->isActionable(), 422, 'Cette demande de signature n\'est plus valide.');
        abort_if($type !== 'drawn' && $type !== 'typed', 422, 'Type de signature invalide.');

        $document    = $signature->document;
        $fileContent = Storage::get($document->file_path);
        $docHash     = hash('sha256', $fileContent ?? '');

        $signature->update([
            'status'         => SignatureStatus::Signed,
            'signature_type' => $type,
            'signature_data' => $data,
            'ip_address'     => $ip,
            'user_agent'     => $userAgent,
            'document_hash'  => $docHash,
            'signed_at'      => now(),
        ]);

        $this->updateDocumentSignatureStatus($document);

        $uploader = $document->uploadedBy;
        if ($uploader) {
            $uploader->notify(new DocumentSigned($signature));
        }
    }

    // ─── Refus de signature ────────────────────────────────────────────────────

    /**
     * Enregistre le refus de signature.
     */
    public function decline(Signature $signature, ?string $reason, string $ip, string $userAgent): void
    {
        abort_unless($signature->isActionable(), 422, 'Cette demande de signature n\'est plus valide.');

        $signature->update([
            'status'          => SignatureStatus::Declined,
            'declined_reason' => $reason,
            'declined_at'     => now(),
            'ip_address'      => $ip,
            'user_agent'      => $userAgent,
        ]);

        $signature->document->update(['signature_status' => 'pending']);

        $uploader = $signature->document->uploadedBy;
        if ($uploader) {
            $uploader->notify(new DocumentSigned($signature));
        }
    }

    // ─── Mise à jour statut document ──────────────────────────────────────────

    /**
     * Met à jour signature_status du document selon l'état des signatures actives.
     * "Actives" = signed ou pending (pas expired ni declined).
     */
    public function updateDocumentSignatureStatus(Document $document): void
    {
        $signatures = Signature::where('document_id', $document->id)->get();

        if ($signatures->isEmpty()) {
            $document->update(['signature_status' => 'none']);
            return;
        }

        $signed  = $signatures->where('status', SignatureStatus::Signed)->count();
        $pending = $signatures->whereIn('status', [SignatureStatus::Pending])->count();

        if ($signed > 0 && $pending === 0) {
            $status = 'completed';
        } elseif ($signed > 0) {
            $status = 'partial';
        } else {
            $status = 'pending';
        }

        $document->update(['signature_status' => $status]);
    }

    // ─── Sérialisation ────────────────────────────────────────────────────────

    /**
     * Sérialise une signature pour Inertia (enrichie avec infos utilisateur si relation chargée).
     */
    public function serializeSignature(Signature $s): array
    {
        $data = [
            'id'              => $s->id,
            'token'           => $s->token,
            'status'          => $s->status->value,
            'status_label'    => $s->status->label(),
            'status_color'    => $s->status->color(),
            'signature_type'  => $s->signature_type,
            'signed_at'       => $s->signed_at?->translatedFormat('j M Y à H:i'),
            'signed_at_iso'   => $s->signed_at?->toIso8601String(),
            'declined_at'     => $s->declined_at?->translatedFormat('j M Y à H:i'),
            'declined_reason' => $s->declined_reason,
            'expires_at'      => $s->expires_at?->translatedFormat('j M Y'),
            'expires_at_iso'  => $s->expires_at?->toIso8601String(),
            'is_actionable'   => $s->isActionable(),
            'ip_address'      => $s->ip_address,
            // Hash tronqué pour l'affichage (preuve lisible)
            'document_hash_short' => $s->document_hash ? substr($s->document_hash, 0, 12).'…'.substr($s->document_hash, -4) : null,
            // Données de signature pour affichage dans Show.vue
            'typed_name'      => $s->signature_type === 'typed' ? $s->signature_data : null,
            'signature_image' => $s->signature_type === 'drawn'  ? $s->signature_data : null,
        ];

        if ($s->relationLoaded('user') && $s->user) {
            $data['user_name']     = $s->user->full_name;
            $data['user_initials'] = $s->user->initials;
            $data['user_email']    = $s->user->email;
        }

        return $data;
    }
}
