<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OvertimeEntry;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimeTrackingService
{
    /**
     * Retourne le pointage d'aujourd'hui pour l'employé, ou null.
     */
    public function getToday(User $user): ?TimeEntry
    {
        return TimeEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('date', today()->toDateString())
            ->first();
    }

    /**
     * Pointer l'arrivée.
     */
    public function clockIn(User $user, ?Request $request = null): TimeEntry
    {
        $existing = $this->getToday($user);

        if ($existing && $existing->clock_in) {
            throw ValidationException::withMessages([
                'clock_in' => 'Vous avez déjà pointé votre arrivée aujourd\'hui.',
            ]);
        }

        $entry = TimeEntry::create([
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'date'       => today()->toDateString(),
            'clock_in'   => now(),
            'source'     => 'clock',
            'ip_address' => $request?->ip(),
        ]);

        return $entry;
    }

    /**
     * Pointer la sortie.
     * Retourne le pointage finalisé + une éventuelle OvertimeEntry auto-détectée.
     *
     * @return array{entry: TimeEntry, overtime: ?OvertimeEntry}
     */
    public function clockOut(User $user): array
    {
        $entry = $this->getToday($user);

        if (! $entry || ! $entry->clock_in) {
            throw ValidationException::withMessages([
                'clock_out' => 'Vous n\'avez pas encore pointé votre arrivée.',
            ]);
        }

        if ($entry->clock_out) {
            throw ValidationException::withMessages([
                'clock_out' => 'Vous avez déjà pointé votre départ aujourd\'hui.',
            ]);
        }

        // Si une pause est en cours, la terminer automatiquement
        if ($entry->isOnBreak()) {
            $this->endBreak($user);
            $entry->refresh();
        }

        $workedMinutes = (int) $entry->clock_in->diffInMinutes(now()) - $entry->total_break_minutes;
        $totalHours    = max(0, round($workedMinutes / 60, 2));

        $entry->update([
            'clock_out'   => now(),
            'total_hours' => $totalHours,
        ]);

        $entry = $entry->fresh();

        // Détection automatique des heures supplémentaires
        $overtimeEntry = app(OvertimeDetectionService::class)->detectAfterClockOut($user, $entry);

        return [
            'entry'    => $entry,
            'overtime' => $overtimeEntry,
        ];
    }

    /**
     * Débuter une pause.
     */
    public function startBreak(User $user): TimeEntry
    {
        $entry = $this->getToday($user);

        if (! $entry || ! $entry->isWorking()) {
            throw ValidationException::withMessages([
                'break' => 'Vous n\'êtes pas en train de travailler.',
            ]);
        }

        $entry->update([
            'break_start' => now(),
            'break_end'   => null,
        ]);

        return $entry->fresh();
    }

    /**
     * Terminer la pause.
     */
    public function endBreak(User $user): TimeEntry
    {
        $entry = $this->getToday($user);

        if (! $entry || ! $entry->isOnBreak()) {
            throw ValidationException::withMessages([
                'break' => 'Vous n\'êtes pas en pause.',
            ]);
        }

        $breakMinutes = (int) $entry->break_start->diffInMinutes(now());

        $entry->update([
            'break_start'         => null,
            'break_end'           => now(),
            'total_break_minutes' => $entry->total_break_minutes + $breakMinutes,
        ]);

        return $entry->fresh();
    }

    /**
     * Sérialise un TimeEntry pour l'envoi à l'interface.
     * Inclut les éventuelles heures sup auto-détectées du jour.
     */
    public function serialize(?TimeEntry $entry): ?array
    {
        if (! $entry) {
            return null;
        }

        // Heures sup auto-détectées liées à ce pointage
        $autoOvertime = $entry->id
            ? OvertimeEntry::withoutGlobalScopes()
                ->where('time_entry_id', $entry->id)
                ->where('source', 'auto')
                ->first()
            : null;

        return [
            'id'                   => $entry->id,
            'date'                 => $entry->date->format('Y-m-d'),
            'status'               => $entry->status,
            'clock_in'             => $entry->clock_in?->format('H:i'),
            'clock_in_ts'          => $entry->clock_in?->toIso8601String(),
            'clock_out'            => $entry->clock_out?->format('H:i'),
            'clock_out_ts'         => $entry->clock_out?->toIso8601String(),
            'break_start_ts'       => $entry->break_start?->toIso8601String(),
            'total_break_minutes'  => $entry->total_break_minutes,
            'worked_minutes'       => $entry->worked_minutes,
            'duration_label'       => $entry->duration_label,
            'overtime_minutes'     => $entry->overtime_minutes,
            'total_hours'          => $entry->total_hours ? (float) $entry->total_hours : null,
            'source'               => $entry->source,
            'auto_overtime'        => $autoOvertime ? [
                'id'          => $autoOvertime->id,
                'hours_label' => $autoOvertime->hours_label,
                'rate_label'  => $autoOvertime->rate_label,
                'status'      => $autoOvertime->status->value,
            ] : null,
        ];
    }

    /**
     * Retourne les entrées de la semaine courante.
     */
    public function getWeekEntries(User $user): array
    {
        $monday = now()->startOfWeek();
        $sunday = now()->endOfWeek();

        $entries = TimeEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monday->toDateString(), $sunday->toDateString()])
            ->get()
            ->keyBy(fn (TimeEntry $e) => $e->date->format('Y-m-d'));

        $result = [];
        for ($day = $monday->copy(); $day->lte($sunday); $day->addDay()) {
            $dateStr = $day->toDateString();
            $entry   = $entries->get($dateStr);
            $result[] = [
                'date'             => $dateStr,
                'day_label'        => $day->translatedFormat('D'),
                'day_num'          => (int) $day->format('j'),
                'is_today'         => $day->isToday(),
                'is_weekend'       => $day->isWeekend(),
                'worked_minutes'   => $entry?->worked_minutes ?? 0,
                'overtime_minutes' => $entry?->overtime_minutes ?? 0,
                'duration_label'   => $entry ? $entry->duration_label : null,
                'status'           => $entry?->status ?? 'idle',
                'clock_in'         => $entry?->clock_in?->format('H:i'),
                'clock_out'        => $entry?->clock_out?->format('H:i'),
            ];
        }

        return $result;
    }
}
