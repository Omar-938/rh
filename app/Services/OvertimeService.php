<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OvertimeStatus;
use App\Models\OvertimeEntry;
use App\Models\User;
use App\Notifications\OvertimeApproved;
use App\Notifications\OvertimeRejected;
use App\Notifications\OvertimeSubmitted;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class OvertimeService
{
    /**
     * Déclarer manuellement des heures supplémentaires.
     */
    public function declare(User $user, array $data): OvertimeEntry
    {
        $date = Carbon::parse($data['date']);

        if ($date->isFuture()) {
            throw ValidationException::withMessages([
                'date' => 'La date ne peut pas être dans le futur.',
            ]);
        }

        $existing = OvertimeEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->where('source', 'manual')
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'date' => 'Vous avez déjà déclaré des heures supplémentaires pour cette date.',
            ]);
        }

        $entry = OvertimeEntry::create([
            'user_id'      => $user->id,
            'company_id'   => $user->company_id,
            'date'         => $date->toDateString(),
            'hours'        => round((float) $data['hours'], 2),
            'rate'         => $data['rate'] ?? '25',
            'source'       => 'manual',
            'status'       => OvertimeStatus::Pending->value,
            'reason'       => $data['reason'] ?? null,
            'compensation' => $data['compensation'] ?? 'payment',
        ]);

        // Notifier les valideurs
        $entry->load('user');
        $this->notifyReviewers($entry, new OvertimeSubmitted($entry));

        return $entry;
    }

    /**
     * Créer automatiquement une entrée depuis la pointeuse.
     */
    public function createFromTimeEntry(User $user, int $timeEntryId, float $hours, string $rate = '25'): OvertimeEntry
    {
        OvertimeEntry::withoutGlobalScopes()
            ->where('time_entry_id', $timeEntryId)
            ->where('source', 'auto')
            ->delete();

        $entry = OvertimeEntry::create([
            'user_id'       => $user->id,
            'company_id'    => $user->company_id,
            'date'          => now()->toDateString(),
            'hours'         => round($hours, 2),
            'rate'          => $rate,
            'source'        => 'auto',
            'time_entry_id' => $timeEntryId,
            'status'        => OvertimeStatus::Pending->value,
            'compensation'  => 'payment',
        ]);

        // Notifier les valideurs (silencieux si l'envoi échoue)
        try {
            $entry->load('user');
            $this->notifyReviewers($entry, new OvertimeSubmitted($entry));
        } catch (\Throwable) {
            // Ne pas bloquer le clock-out si la notification échoue
        }

        return $entry;
    }

    /**
     * Approuver une demande.
     */
    public function approve(OvertimeEntry $entry, User $reviewer, ?string $comment = null): OvertimeEntry
    {
        if (! $entry->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Cette demande n\'est pas en attente de validation.',
            ]);
        }

        $entry->update([
            'status'           => OvertimeStatus::Approved->value,
            'reviewed_by'      => $reviewer->id,
            'reviewed_at'      => now(),
            'reviewer_comment' => $comment,
        ]);

        $entry = $entry->fresh(['user', 'reviewer']);
        $entry->user->notify(new OvertimeApproved($entry));

        return $entry;
    }

    /**
     * Rejeter une demande.
     */
    public function reject(OvertimeEntry $entry, User $reviewer, ?string $comment = null): OvertimeEntry
    {
        if (! $entry->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Cette demande n\'est pas en attente de validation.',
            ]);
        }

        $entry->update([
            'status'           => OvertimeStatus::Rejected->value,
            'reviewed_by'      => $reviewer->id,
            'reviewed_at'      => now(),
            'reviewer_comment' => $comment,
        ]);

        $entry = $entry->fresh(['user', 'reviewer']);
        $entry->user->notify(new OvertimeRejected($entry));

        return $entry;
    }

    /**
     * Statistiques annuelles d'un employé (lit le quota dans les settings société).
     */
    public function getAnnualStats(User $user, ?int $year = null): array
    {
        $year    ??= now()->year;
        $company   = $user->company ?? $user->load('company')->company;
        $quota     = (int) $company->getSetting('overtime_annual_quota', 220);

        $entries = OvertimeEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->forYear($year)
            ->get();

        $approvedHours = $entries->where('status', OvertimeStatus::Approved->value)
            ->sum(fn (OvertimeEntry $e) => (float) $e->hours);

        $pendingHours = $entries->where('status', OvertimeStatus::Pending->value)
            ->sum(fn (OvertimeEntry $e) => (float) $e->hours);

        return [
            'year'            => $year,
            'total_hours'     => round($approvedHours + $pendingHours, 2),
            'approved_hours'  => round($approvedHours, 2),
            'pending_hours'   => round($pendingHours,  2),
            'annual_quota'    => $quota,
            'remaining_quota' => max(0, round($quota - $approvedHours, 2)),
            'quota_pct'       => $quota > 0 ? min(100, round(($approvedHours / $quota) * 100)) : 0,
        ];
    }

    /**
     * Statistiques globales entreprise pour une année.
     */
    public function getCompanyStats(int $companyId, int $year): array
    {
        $entries = OvertimeEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->forYear($year)
            ->get();

        $approved = $entries->where('status', OvertimeStatus::Approved->value)
            ->sum(fn (OvertimeEntry $e) => (float) $e->hours);
        $pending  = $entries->where('status', OvertimeStatus::Pending->value)
            ->sum(fn (OvertimeEntry $e) => (float) $e->hours);
        $rejected = $entries->where('status', OvertimeStatus::Rejected->value)
            ->sum(fn (OvertimeEntry $e) => (float) $e->hours);

        return [
            'year'             => $year,
            'total_entries'    => $entries->count(),
            'approved_hours'   => round($approved, 2),
            'pending_hours'    => round($pending,  2),
            'rejected_hours'   => round($rejected, 2),
            'employees_count'  => $entries->pluck('user_id')->unique()->count(),
        ];
    }

    /**
     * Compteurs heures sup. par employé pour une année.
     *
     * @return array<int, array{id:int,full_name:string,initials:string,avatar_url:?string,approved_hours:float,pending_hours:float,remaining_quota:float,quota_pct:int}>
     */
    public function getEmployeeStats(int $companyId, int $year, int $quota): array
    {
        $employees = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $entries = OvertimeEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->forYear($year)
            ->get()
            ->groupBy('user_id');

        return $employees->map(function (User $emp) use ($entries, $quota) {
            $userEntries = $entries->get($emp->id, collect());
            $approved    = $userEntries->where('status', OvertimeStatus::Approved->value)
                ->sum(fn ($e) => (float) $e->hours);
            $pending     = $userEntries->where('status', OvertimeStatus::Pending->value)
                ->sum(fn ($e) => (float) $e->hours);
            $quotaPct    = $quota > 0 ? min(100, (int) round(($approved / $quota) * 100)) : 0;

            return [
                'id'              => $emp->id,
                'full_name'       => $emp->full_name,
                'initials'        => $emp->initials,
                'avatar_url'      => $emp->avatar_url,
                'approved_hours'  => round($approved, 2),
                'pending_hours'   => round($pending,  2),
                'remaining_quota' => max(0, round($quota - $approved, 2)),
                'quota_pct'       => $quotaPct,
            ];
        })
        ->filter(fn ($s) => $s['approved_hours'] > 0 || $s['pending_hours'] > 0)
        ->sortByDesc('approved_hours')
        ->values()
        ->toArray();
    }

    /**
     * Sérialise une OvertimeEntry pour Inertia.
     */
    public function serialize(OvertimeEntry $entry): array
    {
        return [
            'id'                  => $entry->id,
            'date'                => $entry->date->format('Y-m-d'),
            'date_label'          => $entry->date->translatedFormat('D j M Y'),
            'hours'               => (float) $entry->hours,
            'hours_label'         => $entry->hours_label,
            'rate'                => $entry->rate,
            'rate_label'          => $entry->rate_label,
            'source'              => $entry->source,
            'status'              => $entry->status->value,
            'status_label'        => $entry->status->label(),
            'reason'              => $entry->reason,
            'compensation'        => $entry->compensation,
            'compensation_label'  => $entry->compensation_label,
            'reviewer_comment'    => $entry->reviewer_comment,
            'reviewed_at'         => $entry->reviewed_at?->translatedFormat('j M Y à H:i'),
            'reviewer_name'       => $entry->reviewer?->full_name,
            'user_name'           => $entry->user?->full_name,
            'user_initials'       => $entry->user?->initials,
            'user_avatar_url'     => $entry->user?->avatar_url,
        ];
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Notifie tous les admins + le manager direct de l'employé.
     */
    private function notifyReviewers(OvertimeEntry $entry, mixed $notification): void
    {
        $companyId = $entry->company_id;

        // Tous les admins de la société
        $admins = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        // Manager direct (s'il n'est pas déjà admin)
        $employee = $entry->user;
        if ($employee->manager_id) {
            $manager = User::withoutGlobalScopes()->find($employee->manager_id);
            if ($manager && $manager->role->value !== 'admin') {
                $manager->notify($notification);
            }
        }
    }
}
