<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PayslipStatus;
use App\Models\Company;
use App\Models\Payslip;
use App\Models\User;
use App\Notifications\PayslipDistributed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipService
{
    private const CIPHER = 'AES-256-CBC';
    private const IV_LEN = 16;

    // ─── Chiffrement ────────────────────────────────────────────────────────────

    private function getKey(): string
    {
        $appKey = config('app.key');
        $decoded = str_starts_with($appKey, 'base64:')
            ? base64_decode(substr($appKey, 7))
            : $appKey;

        return substr(str_pad($decoded, 32, "\0"), 0, 32);
    }

    private function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::IV_LEN);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $this->getKey(), OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Échec du chiffrement du bulletin.');
        }

        return $iv . $encrypted;
    }

    private function decrypt(string $ciphertext): string
    {
        $iv   = substr($ciphertext, 0, self::IV_LEN);
        $enc  = substr($ciphertext, self::IV_LEN);
        $plain = openssl_decrypt($enc, self::CIPHER, $this->getKey(), OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            throw new \RuntimeException('Échec du déchiffrement du bulletin.');
        }

        return $plain;
    }

    // ─── Upload ─────────────────────────────────────────────────────────────────

    /**
     * Chiffre et stocke un bulletin de paie.
     * Si un bulletin existe déjà pour la même période/employé, il est remplacé.
     */
    public function upload(UploadedFile $file, User $uploader, array $data): Payslip
    {
        $companyId   = $uploader->company_id;
        $userId      = ! empty($data['user_id']) ? (int) $data['user_id'] : null;
        $periodYear  = (int) $data['period_year'];
        $periodMonth = (int) $data['period_month'];

        // Stocker le fichier chiffré
        $yearMonth = sprintf('%04d/%02d', $periodYear, $periodMonth);
        $uuid      = Str::uuid()->toString();
        $storedAs  = "payslips/{$companyId}/{$yearMonth}/{$uuid}.enc";

        $plaintext = file_get_contents($file->getRealPath());
        Storage::put($storedAs, $this->encrypt($plaintext));

        // Remplacer un bulletin existant pour la même période/employé
        $existing = Payslip::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('period_year', $periodYear)
            ->where('period_month', $periodMonth)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->when($userId === null, fn ($q) => $q->whereNull('user_id'))
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            // Supprimer l'ancien fichier physique
            if (Storage::exists($existing->file_path)) {
                Storage::delete($existing->file_path);
            }
            $existing->update([
                'uploaded_by'       => $uploader->id,
                'original_filename' => $file->getClientOriginalName(),
                'file_path'         => $storedAs,
                'file_size'         => $file->getSize(),
                'status'            => PayslipStatus::Draft,
                'distributed_at'    => null,
                'is_viewed'         => false,
                'viewed_at'         => null,
                'notes'             => $data['notes'] ?? null,
            ]);

            return $existing->fresh();
        }

        return Payslip::create([
            'company_id'        => $companyId,
            'user_id'           => $userId,
            'uploaded_by'       => $uploader->id,
            'period_year'       => $periodYear,
            'period_month'      => $periodMonth,
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $storedAs,
            'file_size'         => $file->getSize(),
            'is_encrypted'      => true,
            'status'            => PayslipStatus::Draft,
            'notes'             => $data['notes'] ?? null,
        ]);
    }

    // ─── Téléchargement ─────────────────────────────────────────────────────────

    /**
     * Déchiffre et sert le bulletin en téléchargement sécurisé.
     * Marque le bulletin comme lu si c'est l'employé qui télécharge.
     */
    public function download(Payslip $payslip, User $user): StreamedResponse
    {
        abort_unless(Storage::exists($payslip->file_path), 404, 'Bulletin introuvable.');

        $ciphertext = Storage::get($payslip->file_path);
        $plaintext  = $payslip->is_encrypted
            ? $this->decrypt($ciphertext)
            : $ciphertext;

        // Marquer comme lu si c'est l'employé propriétaire
        if ($payslip->user_id === $user->id && ! $payslip->is_viewed) {
            $payslip->update(['is_viewed' => true, 'viewed_at' => now()]);
        }

        $filename = $payslip->original_filename;

        return response()->streamDownload(
            fn () => print($plaintext),
            $filename,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Length'      => strlen($plaintext),
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    // ─── Accès ──────────────────────────────────────────────────────────────────

    public function canAccess(Payslip $payslip, User $user): bool
    {
        if ($payslip->company_id !== $user->company_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Employé : seulement son bulletin, s'il a été distribué
        return $payslip->user_id === $user->id
            && $payslip->status === PayslipStatus::Distributed;
    }

    // ─── Parsing nom de fichier ─────────────────────────────────────────────────

    /**
     * Tente de deviner la période (année/mois) depuis le nom de fichier.
     * Retourne ['year' => int|null, 'month' => int|null].
     */
    public function parseFilenameForPeriod(string $filename): array
    {
        $base = preg_replace('/\.[^.]+$/', '', $filename);

        // Format YYYYMM collé (ex: 202501, 20250112)
        if (preg_match('/(\d{4})(0[1-9]|1[0-2])(?!\d)/', $base, $m)) {
            $y = (int) $m[1];
            if ($y >= 2000 && $y <= 2099) {
                return ['year' => $y, 'month' => (int) $m[2]];
            }
        }

        $tokens = preg_split('/[\s_\-\.]+/', mb_strtolower($base));
        $year   = null;
        $month  = null;

        // Noms de mois français
        $monthMap = [
            'janvier' => 1, 'jan' => 1,
            'fevrier' => 2, 'février' => 2, 'fev' => 2, 'fév' => 2,
            'mars'    => 3, 'mar' => 3,
            'avril'   => 4, 'avr' => 4,
            'mai'     => 5,
            'juin'    => 6,
            'juillet' => 7, 'juil' => 7, 'jul' => 7,
            'aout'    => 8, 'août' => 8,
            'septembre' => 9, 'sep' => 9, 'sept' => 9,
            'octobre'   => 10, 'oct' => 10,
            'novembre'  => 11, 'nov' => 11,
            'decembre'  => 12, 'décembre' => 12, 'dec' => 12, 'déc' => 12,
        ];

        foreach ($tokens as $token) {
            if (isset($monthMap[$token])) {
                $month = $monthMap[$token];
                continue;
            }

            if (preg_match('/^\d{4}$/', $token)) {
                $n = (int) $token;
                if ($n >= 2000 && $n <= 2099) {
                    $year = $n;
                }
            }

            if ($year === null && preg_match('/^(0?[1-9]|1[0-2])$/', $token)) {
                $month = (int) $token;
            }
        }

        return ['year' => $year, 'month' => $month];
    }

    /**
     * Tente de trouver l'employé correspondant au nom de fichier.
     * Retourne le User ou null.
     */
    public function guessEmployee(string $filename, Collection $employees): ?User
    {
        $base      = preg_replace('/\.[^.]+$/', '', $filename);
        $tokens    = preg_split('/[\s_\-\.]+/', mb_strtolower($base));

        // Exclure les mots-clés non-noms
        $stopWords = [
            'bulletin', 'bulletins', 'salaire', 'paie', 'paye', 'fiche',
            'bp', 'bs', 'rh', 'hr', 'january', 'february', 'salary',
            'de', 'du', 'le', 'la', 'les', 'et',
        ];
        $nameTokens = array_filter($tokens, fn ($t) => ! in_array($t, $stopWords, true)
            && ! preg_match('/^\d+$/', $t)
            && mb_strlen($t) >= 2
        );

        $bestMatch = null;
        $bestScore = 0;

        foreach ($employees as $employee) {
            $parts      = preg_split('/\s+/', mb_strtolower($employee->full_name));
            $score      = 0;

            foreach ($parts as $part) {
                $partShort = mb_substr($part, 0, 4);
                foreach ($nameTokens as $token) {
                    if ($token === $part) {
                        $score += 3;
                    } elseif (str_starts_with($token, $partShort) || str_starts_with($part, mb_substr($token, 0, 4))) {
                        $score += mb_strlen($partShort) >= 4 ? 2 : 1;
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $employee;
            }
        }

        // Seuil minimum pour éviter les faux-positifs
        return $bestScore >= 2 ? $bestMatch : null;
    }

    // ─── Distribution ───────────────────────────────────────────────────────────

    /**
     * Distribue un bulletin : passe en statut "distributed" et notifie l'employé.
     */
    public function distribute(Payslip $payslip, User $distributor): void
    {
        DB::transaction(function () use ($payslip) {
            $payslip->update([
                'status'         => PayslipStatus::Distributed,
                'distributed_at' => now(),
            ]);

            if ($payslip->user_id !== null) {
                $employee = $payslip->user ?? User::find($payslip->user_id);
                $employee?->notify(new PayslipDistributed($payslip));
            }
        });
    }

    // ─── Suppression ────────────────────────────────────────────────────────────

    public function deleteFile(Payslip $payslip): void
    {
        if (Storage::exists($payslip->file_path)) {
            Storage::delete($payslip->file_path);
        }
    }

    // ─── Sérialisation ──────────────────────────────────────────────────────────

    public function serialize(Payslip $p): array
    {
        return [
            'id'                => $p->id,
            'period_year'       => $p->period_year,
            'period_month'      => $p->period_month,
            'period_label'      => $p->period_label,
            'period_short'      => $p->period_short,
            'original_filename' => $p->original_filename,
            'file_size_label'   => $p->file_size_label,
            'status'            => $p->status->value,
            'status_label'      => $p->status->label(),
            'is_distributed'    => $p->isDistributed(),
            'is_draft'          => $p->isDraft(),
            'distributed_at'    => $p->distributed_at?->translatedFormat('j M Y'),
            'is_viewed'         => $p->is_viewed,
            'viewed_at'         => $p->viewed_at?->translatedFormat('j M Y à H:i'),
            'notes'             => $p->notes,
            'user_id'           => $p->user_id,
            'user_name'         => $p->user?->full_name,
            'user_initials'     => $p->user?->initials,
            'uploaded_by_name'  => $p->uploadedBy?->full_name,
            'created_at'        => $p->created_at->translatedFormat('j M Y'),
            'download_url'      => route('payslips.download', $p->id),
        ];
    }
}
