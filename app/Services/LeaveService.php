<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AcquisitionType;
use App\Enums\LeaveStatus;
use App\Enums\ScheduleType;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestCancelled;
use App\Notifications\LeaveRequestRejected;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    /**
     * Calcule le nombre de jours ouvrés entre deux dates.
     * Exclut les week-ends et les jours fériés de l'entreprise.
     */
    public function calculateWorkingDays(
        string  $startDate,
        string  $endDate,
        ?string $startHalf = null,
        ?string $endHalf   = null,
        ?int    $companyId = null,
    ): float {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 0.0;
        }

        // Charge les jours fériés de l'entreprise sur la période
        $holidays = [];
        if ($companyId !== null) {
            $holidays = Holiday::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->map(fn ($d) => ($d instanceof Carbon ? $d : Carbon::parse($d))->format('Y-m-d'))
                ->toArray();
        }

        $days = 0.0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (! $date->isWeekend() && ! in_array($date->format('Y-m-d'), $holidays, true)) {
                $days++;
            }
        }

        // Demi-journée de début (après-midi) : -0.5j
        if ($startHalf === 'afternoon' && $days > 0) {
            $days -= 0.5;
        }

        // Demi-journée de fin (matin) : -0.5j
        if ($endHalf === 'morning' && $days > 0) {
            $days -= 0.5;
        }

        return max(0.0, $days);
    }

    /**
     * Crée une demande de congé et incrémente le solde pending.
     */
    public function create(User $employee, array $data): LeaveRequest
    {
        $leaveRequest = DB::transaction(function () use ($employee, $data): LeaveRequest {
            $days = $this->calculateWorkingDays(
                $data['start_date'],
                $data['end_date'],
                $data['start_half'] ?? null,
                $data['end_half']   ?? null,
                $employee->company_id,
            );

            $leaveRequest = LeaveRequest::create([
                'user_id'          => $employee->id,
                'company_id'       => $employee->company_id,
                'leave_type_id'    => $data['leave_type_id'],
                'start_date'       => $data['start_date'],
                'end_date'         => $data['end_date'],
                'start_half'       => $data['start_half'] ?? null,
                'end_half'         => $data['end_half']   ?? null,
                'days_count'       => $days,
                'status'           => LeaveStatus::Pending,
                'employee_comment' => $data['employee_comment'] ?? null,
            ]);

            // Incrémente les jours pendants dans le solde
            $this->incrementPending($employee, $data['leave_type_id'], $days);

            return $leaveRequest;
        });

        // Notifie les admins et le manager de l'employé (hors transaction)
        $leaveRequest->load(['user', 'leaveType']);
        $this->notifyReviewers($leaveRequest, new LeaveRequestSubmitted($leaveRequest));

        return $leaveRequest;
    }

    /**
     * Approuve une demande de congé.
     */
    public function approve(LeaveRequest $request, User $reviewer, ?string $comment = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $comment): void {
            $request->update([
                'status'          => LeaveStatus::Approved,
                'reviewed_by'     => $reviewer->id,
                'reviewed_at'     => now(),
                'reviewer_comment'=> $comment,
            ]);

            // Déplace pending → used dans le solde
            $this->movePendingToUsed($request->user, $request->leave_type_id, (float) $request->days_count);

            // Crée des entrées Schedule pour chaque jour ouvré de la période
            $this->createScheduleEntries($request, $reviewer);
        });

        // Notifie l'employé (hors transaction)
        $request->refresh()->load(['user', 'leaveType', 'reviewer']);
        $request->user->notify(new LeaveRequestApproved($request));
    }

    /**
     * Rejette une demande de congé.
     */
    public function reject(LeaveRequest $request, User $reviewer, string $comment): void
    {
        DB::transaction(function () use ($request, $reviewer, $comment): void {
            $request->update([
                'status'          => LeaveStatus::Rejected,
                'reviewed_by'     => $reviewer->id,
                'reviewed_at'     => now(),
                'reviewer_comment'=> $comment,
            ]);

            // Restitue les jours pending dans le solde
            $this->releasePending($request->user, $request->leave_type_id, (float) $request->days_count);
        });

        // Notifie l'employé (hors transaction)
        $request->refresh()->load(['user', 'leaveType', 'reviewer']);
        $request->user->notify(new LeaveRequestRejected($request));
    }

    /**
     * Annule une demande (par l'employé ou un admin).
     */
    public function cancel(LeaveRequest $request): void
    {
        $cancelledByEmployee = auth()->id() === $request->user_id;

        DB::transaction(function () use ($request): void {
            $wasApproved = $request->isApproved();

            $request->update([
                'status'       => LeaveStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            if ($wasApproved) {
                // Restitue les jours used dans le solde + supprime les entrées Schedule
                $this->releaseUsed($request->user, $request->leave_type_id, (float) $request->days_count);
                $this->removeScheduleEntries($request);
            } else {
                // Restitue les jours pending
                $this->releasePending($request->user, $request->leave_type_id, (float) $request->days_count);
            }
        });

        // Notifications hors transaction
        $request->refresh()->load(['user', 'leaveType']);

        if ($cancelledByEmployee) {
            // Annulation par l'employé → notifier les admins/manager
            $this->notifyReviewers($request, new LeaveRequestCancelled($request, byEmployee: true));
        } else {
            // Annulation par un admin → notifier l'employé
            $request->user->notify(new LeaveRequestCancelled($request, byEmployee: false));
        }
    }

    /**
     * Vérifie si l'employé a suffisamment de solde disponible.
     * Retourne null si OK, ou un message d'erreur.
     */
    public function checkBalance(User $employee, int $leaveTypeId, float $days): ?string
    {
        $leaveType = LeaveType::find($leaveTypeId);

        // Types sans acquisition → pas de vérification de solde (ex: maladie, sans-solde)
        if (! $leaveType || $leaveType->acquisition_type === AcquisitionType::None) {
            return null;
        }

        $balance = $this->getOrCreateBalance($employee, $leaveTypeId);

        if ($balance->effective_remaining < $days) {
            return sprintf(
                'Solde insuffisant : %.1f j disponibles, %.1f j demandés.',
                $balance->effective_remaining,
                $days
            );
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers privés — gestion des soldes
    // -------------------------------------------------------------------------

    private function getOrCreateBalance(User $employee, int $leaveTypeId): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            ['user_id' => $employee->id, 'leave_type_id' => $leaveTypeId, 'year' => now()->year],
            ['company_id' => $employee->company_id, 'allocated' => 0, 'used' => 0, 'pending' => 0]
        );
    }

    private function incrementPending(User $employee, int $leaveTypeId, float $days): void
    {
        $leaveType = LeaveType::find($leaveTypeId);
        if (! $leaveType || $leaveType->acquisition_type === AcquisitionType::None) {
            return;
        }

        $balance = $this->getOrCreateBalance($employee, $leaveTypeId);
        $balance->increment('pending', $days);
    }

    private function movePendingToUsed(User $employee, int $leaveTypeId, float $days): void
    {
        $leaveType = LeaveType::find($leaveTypeId);
        if (! $leaveType || $leaveType->acquisition_type === AcquisitionType::None) {
            return;
        }

        $balance = $this->getOrCreateBalance($employee, $leaveTypeId);
        $balance->decrement('pending', min($days, (float) $balance->pending));
        $balance->increment('used', $days);
    }

    private function releasePending(User $employee, int $leaveTypeId, float $days): void
    {
        $leaveType = LeaveType::find($leaveTypeId);
        if (! $leaveType || $leaveType->acquisition_type === AcquisitionType::None) {
            return;
        }

        $balance = $this->getOrCreateBalance($employee, $leaveTypeId);
        $balance->decrement('pending', min($days, (float) $balance->pending));
    }

    private function releaseUsed(User $employee, int $leaveTypeId, float $days): void
    {
        $leaveType = LeaveType::find($leaveTypeId);
        if (! $leaveType || $leaveType->acquisition_type === AcquisitionType::None) {
            return;
        }

        $balance = $this->getOrCreateBalance($employee, $leaveTypeId);
        $balance->decrement('used', min($days, (float) $balance->used));
    }

    // -------------------------------------------------------------------------
    // Helpers privés — Planning
    // -------------------------------------------------------------------------

    private function createScheduleEntries(LeaveRequest $request, User $creator): void
    {
        $period = CarbonPeriod::create(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        foreach ($period as $date) {
            if ($date->isWeekend()) {
                continue;
            }

            Schedule::updateOrCreate(
                ['user_id' => $request->user_id, 'date' => $date->format('Y-m-d')],
                [
                    'company_id'    => $request->company_id,
                    'type'          => ScheduleType::Leave,
                    'start_time'    => null,
                    'end_time'      => null,
                    'break_minutes' => 0,
                    'notes'         => "Congé : {$request->leaveType->name}",
                    'created_by'    => $creator->id,
                ]
            );
        }
    }

    private function removeScheduleEntries(LeaveRequest $request): void
    {
        Schedule::where('user_id', $request->user_id)
            ->where('type', ScheduleType::Leave)
            ->whereBetween('date', [$request->start_date->format('Y-m-d'), $request->end_date->format('Y-m-d')])
            ->delete();
    }

    // -------------------------------------------------------------------------
    // Helpers privés — Notifications
    // -------------------------------------------------------------------------

    /**
     * Envoie une notification à tous les admins de la company
     * + au manager direct de l'employé (s'il existe et est différent des admins).
     */
    private function notifyReviewers(LeaveRequest $request, mixed $notification): void
    {
        $companyId = $request->company_id;

        // Récupère les admins de la company
        $admins = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('role', 'admin')
            ->get();

        foreach ($admins as $admin) {
            // Ne pas notifier l'employé lui-même
            if ($admin->id !== $request->user_id) {
                $admin->notify(clone $notification);
            }
        }

        // Manager direct (s'il n'est pas déjà dans les admins)
        $managerId = $request->user->manager_id ?? null;
        if ($managerId && ! $admins->contains('id', $managerId)) {
            $manager = User::withoutGlobalScopes()
                ->where('id', $managerId)
                ->where('is_active', true)
                ->first();

            if ($manager && $manager->id !== $request->user_id) {
                $manager->notify(clone $notification);
            }
        }
    }
}
