<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayService
{
    /**
     * Retourne les dates de jours fériés d'une entreprise pour une plage de dates.
     * Utilisé pour exclure les fériés du calcul des jours ouvrés.
     *
     * @return array<string> Liste de dates au format 'Y-m-d'
     */
    public function getHolidayDates(int $companyId, string $from, string $to): array
    {
        return Holiday::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$from, $to])
            ->pluck('date')
            ->map(fn ($d) => $d instanceof Carbon ? $d->format('Y-m-d') : Carbon::parse($d)->format('Y-m-d'))
            ->toArray();
    }

    /**
     * Retourne la liste des 11 jours fériés français pour une année donnée.
     *
     * @return array<array{name: string, date: string, is_recurring: bool}>
     */
    public static function frenchHolidays(int $year): array
    {
        $easter = self::computeEaster($year);

        return [
            ['name' => "Jour de l'An",      'date' => "{$year}-01-01", 'is_recurring' => true],
            ['name' => 'Lundi de Pâques',    'date' => $easter->copy()->addDays(1)->format('Y-m-d'),  'is_recurring' => false],
            ['name' => 'Fête du Travail',    'date' => "{$year}-05-01", 'is_recurring' => true],
            ['name' => 'Victoire 1945',      'date' => "{$year}-05-08", 'is_recurring' => true],
            ['name' => 'Ascension',          'date' => $easter->copy()->addDays(39)->format('Y-m-d'), 'is_recurring' => false],
            ['name' => 'Lundi de Pentecôte', 'date' => $easter->copy()->addDays(50)->format('Y-m-d'), 'is_recurring' => false],
            ['name' => 'Fête Nationale',     'date' => "{$year}-07-14", 'is_recurring' => true],
            ['name' => 'Assomption',         'date' => "{$year}-08-15", 'is_recurring' => true],
            ['name' => 'Toussaint',          'date' => "{$year}-11-01", 'is_recurring' => true],
            ['name' => 'Armistice',          'date' => "{$year}-11-11", 'is_recurring' => true],
            ['name' => 'Noël',               'date' => "{$year}-12-25", 'is_recurring' => true],
        ];
    }

    /**
     * Calcule la date de Pâques (dimanche) pour une année donnée.
     * Algorithme de Meeus/Jones/Butcher.
     */
    public static function computeEaster(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);

        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::createFromDate($year, $month, $day);
    }
}
