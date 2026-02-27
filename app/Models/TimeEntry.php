<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'date',
        'clock_in',
        'break_start',
        'break_end',
        'clock_out',
        'total_hours',
        'total_break_minutes',
        'overtime_minutes',
        'source',
        'ip_address',
        'location_lat',
        'location_lng',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'clock_in'    => 'datetime',
            'break_start' => 'datetime',
            'break_end'   => 'datetime',
            'clock_out'   => 'datetime',
            'total_hours' => 'decimal:2',
            'total_break_minutes' => 'integer',
            'overtime_minutes'    => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // État du pointage
    // -------------------------------------------------------------------------

    /**
     * Statut courant : 'idle' | 'working' | 'on_break' | 'done'
     */
    public function getStatusAttribute(): string
    {
        if (! $this->clock_in) {
            return 'idle';
        }
        if ($this->clock_out) {
            return 'done';
        }
        if ($this->break_start && ! $this->break_end) {
            return 'on_break';
        }
        return 'working';
    }

    public function isWorking(): bool  { return $this->status === 'working';  }
    public function isOnBreak(): bool  { return $this->status === 'on_break'; }
    public function isDone(): bool     { return $this->status === 'done';     }
    public function isIdle(): bool     { return $this->status === 'idle';     }

    // -------------------------------------------------------------------------
    // Accesseurs calculés
    // -------------------------------------------------------------------------

    /**
     * Minutes travaillées (sessions terminées).
     */
    public function getWorkedMinutesAttribute(): int
    {
        if (! $this->clock_in) {
            return 0;
        }

        $end = $this->clock_out ?? now();

        return (int) max(0, $this->clock_in->diffInMinutes($end) - $this->total_break_minutes);
    }

    /**
     * Minutes de pause en cours (si sur pause).
     */
    public function getCurrentBreakMinutesAttribute(): int
    {
        if (! $this->break_start || $this->break_end) {
            return 0;
        }

        return (int) $this->break_start->diffInMinutes(now());
    }

    /**
     * Total pause incluant la pause en cours.
     */
    public function getTotalBreakDisplayAttribute(): int
    {
        return $this->total_break_minutes + $this->current_break_minutes;
    }

    /**
     * Label de durée travaillée (ex: "7h30").
     */
    public function getDurationLabelAttribute(): string
    {
        return self::minutesToLabel($this->worked_minutes);
    }

    /**
     * Progression vers l'objectif journalier (0.0–1.0).
     */
    public function getProgressAttribute(): float
    {
        $target = 7 * 60; // 7h par défaut
        if ($target <= 0) {
            return 0.0;
        }

        return min(1.0, $this->worked_minutes / $target);
    }

    // -------------------------------------------------------------------------
    // Helpers statiques
    // -------------------------------------------------------------------------

    public static function minutesToLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0h00';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h > 0
            ? sprintf('%dh%02d', $h, $m)
            : sprintf('%d min', $m);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('clock_out');
    }
}
