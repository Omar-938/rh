<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    private const CIPHER  = 'AES-256-CBC';
    private const IV_LEN  = 16; // AES bloc = 128 bits

    // ─── Chiffrement ──────────────────────────────────────────────────────────

    /**
     * Dérive une clé 32 octets depuis APP_KEY.
     */
    private function getKey(): string
    {
        $appKey = config('app.key');

        // La clé Laravel est stockée sous la forme "base64:xxxx"
        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7));
        } else {
            $decoded = $appKey;
        }

        // AES-256 attend exactement 32 octets
        return substr(str_pad($decoded, 32, "\0"), 0, 32);
    }

    /**
     * Chiffre le contenu binaire et retourne IV + données chiffrées.
     */
    private function encrypt(string $plaintext): string
    {
        $iv        = random_bytes(self::IV_LEN);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $this->getKey(), OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Échec du chiffrement du document.');
        }

        return $iv . $encrypted;
    }

    /**
     * Déchiffre le contenu (IV préfixé + données chiffrées).
     */
    private function decrypt(string $ciphertext): string
    {
        $iv        = substr($ciphertext, 0, self::IV_LEN);
        $encrypted = substr($ciphertext, self::IV_LEN);
        $plain     = openssl_decrypt($encrypted, self::CIPHER, $this->getKey(), OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            throw new \RuntimeException('Échec du déchiffrement du document.');
        }

        return $plain;
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    /**
     * Chiffre et stocke un fichier uploadé, crée l'entrée Document.
     */
    public function upload(UploadedFile $file, User $uploader, array $data): Document
    {
        $companyId = $uploader->company_id;
        $yearMonth = now()->format('Y/m');
        $uuid      = Str::uuid()->toString();
        $storedAs  = "documents/{$companyId}/{$yearMonth}/{$uuid}.enc";

        // Lire + chiffrer en mémoire (max 20 Mo via validation)
        $plaintext = file_get_contents($file->getRealPath());
        $encrypted = $this->encrypt($plaintext);

        Storage::put($storedAs, $encrypted);

        return Document::create([
            'company_id'          => $companyId,
            'user_id'             => $data['user_id'] ?? null,
            'uploaded_by'         => $uploader->id,
            'name'                => $data['name'],
            'original_filename'   => $file->getClientOriginalName(),
            'category'            => $data['category'] ?? 'other',
            'mime_type'           => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'           => $file->getSize(),
            'file_path'           => $storedAs,
            'is_encrypted'        => true,
            'requires_signature'  => (bool) ($data['requires_signature'] ?? false),
            'signature_status'    => 'none',
            'expires_at'          => $data['expires_at'] ?? null,
            'notes'               => $data['notes'] ?? null,
        ]);
    }

    /**
     * Déchiffre et sert le fichier en téléchargement sécurisé.
     */
    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::exists($document->file_path), 404, 'Fichier introuvable.');

        $ciphertext = Storage::get($document->file_path);
        $plaintext  = $document->is_encrypted
            ? $this->decrypt($ciphertext)
            : $ciphertext;

        $filename = $document->original_filename;
        $mime     = $document->mime_type;

        return response()->streamDownload(
            function () use ($plaintext) { echo $plaintext; },
            $filename,
            [
                'Content-Type'        => $mime,
                'Content-Length'      => strlen($plaintext),
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Supprime le fichier physique et la ligne (soft delete déjà géré par le modèle).
     */
    public function deleteFile(Document $document): void
    {
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }
    }

    /**
     * Vérifie si un utilisateur peut accéder à un document.
     */
    public function canAccess(Document $document, User $user): bool
    {
        // Doit appartenir à la même société
        if ($document->company_id !== $user->company_id) {
            return false;
        }

        // Admin/manager : accès à tout
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Employé : seulement ses propres documents + documents d'entreprise
        return $document->user_id === null || $document->user_id === $user->id;
    }

    /**
     * Sérialise un Document pour Inertia.
     */
    public function serialize(Document $document): array
    {
        return [
            'id'               => $document->id,
            'name'             => $document->name,
            'original_filename'=> $document->original_filename,
            'category'         => $document->category->value,
            'category_label'   => $document->category->label(),
            'mime_type'        => $document->mime_type,
            'mime_icon'        => $document->mime_icon,
            'file_size'        => $document->file_size,
            'file_size_label'  => $document->file_size_label,
            'requires_signature'  => $document->requires_signature,
            'signature_status'    => $document->signature_status,
            'is_expired'       => $document->is_expired,
            'is_expiring_soon' => $document->is_expiring_soon,
            'expires_at'       => $document->expires_at?->format('Y-m-d'),
            'expires_at_label' => $document->expires_at?->translatedFormat('j M Y'),
            'notes'            => $document->notes,
            'is_company_doc'   => $document->isCompanyDocument(),
            'user_id'          => $document->user_id,
            'user_name'        => $document->user?->full_name,
            'user_initials'    => $document->user?->initials,
            'user_avatar_url'  => $document->user?->avatar_url,
            'uploaded_by_name' => $document->uploadedBy?->full_name,
            'created_at'       => $document->created_at->translatedFormat('j M Y'),
            'download_url'          => route('documents.download', $document->id),
            'certificate_url'       => $document->requires_signature
                ? route('documents.certificate', $document->id)
                : null,
            'request_signature_url' => $document->requires_signature && $document->user_id !== null
                ? route('documents.request-signature', $document->id)
                : null,
        ];
    }
}
