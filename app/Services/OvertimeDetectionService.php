<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OvertimeEntry;
use App\Models\TimeEntry;
use App\Models\User;

class OvertimeDetectionService
{
    // Valeurs légales par défaut (si non configurées en settings)
    private const DEFAULT_THRESHOLD_MINUTES  = 15; // Seuil de grâce
    private const DEFAULT_RATE_50_THRESHOLD  = 43; // Heures hebdo → +50%
    private const LEGAL_WEEKLY_H             = 35; // Durée légale de référence

    public function __construct(private OvertimeService $overtimeService) {}

    /**
     * Analyse le pointage après clock-out et crée une OvertimeEntry si nécessaire.
     * Retourne l'entrée créée/mise à jour ou null si aucun dépassement.
     */
    public function detectAfterClockOut(User $user, TimeEntry $entry): ?OvertimeEntry
    {
        // Vérifier que la détection auto est activée pour cette entreprise
        $company = $user->company ?? $user->load('company')->company;
        if (! $company->getSetting('overtime_auto_detect', true)) {
            return null;
        }

        // ── Seuils configurés ────────────────────────────────────────────────
        $thresholdMinutes = (int) $company->getSetting('overtime_threshold_minutes', self::DEFAULT_THRESHOLD_MINUTES);
        $rate50Threshold  = (int) $company->getSetting('overtime_rate_50_threshold', self::DEFAULT_RATE_50_THRESHOLD);

        // ── Cible journalière ────────────────────────────────────────────────
        $weeklyHoursTarget  = (float) ($user->weekly_hours ?? self::LEGAL_WEEKLY_H);
        $workDaysPerWeek    = $company->work_days_per_week; // défaut: 5

        // Cible en minutes pour la journée
        $dailyTargetMinutes = (int) round(($weeklyHoursTarget / $workDaysPerWeek) * 60);

        // Minutes travaillées aujourd'hui (nettes de pauses)
        $workedMinutesToday = $entry->worked_minutes;

        // Dépassement de la journée
        $overtimeTodayMin = $workedMinutesToday - $dailyTargetMinutes;

        // Inférieur au seuil de grâce → supprimer toute entrée auto existante et sortir
        if ($overtimeTodayMin < $thresholdMinutes) {
            OvertimeEntry::withoutGlobalScopes()
                ->where('time_entry_id', $entry->id)
                ->where('source', 'auto')
                ->delete();

            // Remettre overtime_minutes à 0
            $entry->update(['overtime_minutes' => 0]);

            return null;
        }

        // ── Calcul du taux : basé sur le total hebdomadaire ──────────────────
        $rate = $this->computeRate($user, $entry, $overtimeTodayMin, $rate50Threshold);

        // ── Mettre à jour overtime_minutes dans time_entries ─────────────────
        $entry->update(['overtime_minutes' => $overtimeTodayMin]);

        // ── Créer / mettre à jour l'OvertimeEntry auto ───────────────────────
        $hours = round($overtimeTodayMin / 60, 2);

        return $this->overtimeService->createFromTimeEntry(
            user:        $user,
            timeEntryId: $entry->id,
            hours:       $hours,
            rate:        $rate,
        );
    }

    /**
     * Détermine le taux de majoration (+25% ou +50%) selon le total hebdomadaire.
     *
     * Règles légales françaises :
     *  - 36e à (seuil - 1)e heure → +25%
     *  - Au-delà du seuil (défaut 43h) → +50%
     *
     * On prend la tranche qui représente le plus gros des heures sup de la journée.
     */
    private function computeRate(User $user, TimeEntry $todayEntry, int $overtimeTodayMin, int $rate50Threshold): string
    {
        // Total travaillé cette semaine (toutes entrées terminées sauf aujourd'hui)
        $monday = now()->startOfWeek()->toDateString();
        $sunday = now()->endOfWeek()->toDateString();

        $weekEntries = TimeEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monday, $sunday])
            ->where('id', '!=', $todayEntry->id)
            ->get();

        $prevWeekMinutes = $weekEntries->sum(fn (TimeEntry $e) => $e->worked_minutes);

        // Total hebdo incluant aujourd'hui
        $totalWeekMinutes = $prevWeekMinutes + $todayEntry->worked_minutes;
        $totalWeekHours   = $totalWeekMinutes / 60;

        // Si le total dépasse le seuil 50% → taux 50% sur la partie au-delà
        if ($totalWeekHours > $rate50Threshold) {
            // Les minutes dans la tranche 50% (au-delà du seuil)
            $minutes50 = (int) (($totalWeekHours - $rate50Threshold) * 60);
            // Si plus de la moitié des heures sup de la journée sont à 50% → on utilise 50%
            return $minutes50 >= ($overtimeTodayMin / 2) ? '50' : '25';
        }

        return '25';
    }

    /**
     * Recalcule toutes les heures sup de la semaine en cours pour un utilisateur.
     * Utile si les données changent (correction de pointage, etc.).
     */
    public function reprocessWeek(User $user): void
    {
        $monday = now()->startOfWeek()->toDateString();
        $sunday = now()->endOfWeek()->toDateString();

        $entries = TimeEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monday, $sunday])
            ->whereNotNull('clock_out')
            ->orderBy('date')
            ->get();

        foreach ($entries as $entry) {
            $this->detectAfterClockOut($user, $entry);
        }
    }
}
