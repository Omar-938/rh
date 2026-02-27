<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScheduleType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'date',
        'type',
        'start_time',
        'end_time',
        'break_minutes',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'type'          => ScheduleType::class,
            'break_minutes' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Accesseurs
    // -------------------------------------------------------------------------

    /**
     * Durée nette travaillée en minutes (sans les pauses).
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end   = \Carbon\Carbon::parse($this->end_time);
        $total = $end->diffInMinutes($start);

        return max(0, $total - $this->break_minutes);
    }

    /**
     * Durée formatée "7h30".
     */
    public function getDurationLabelAttribute(): ?string
    {
        $minutes = $this->duration_minutes;
        if ($minutes === null) {
            return null;
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m > 0 ? "{$h}h{$m}" : "{$h}h";
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForWeek($query, string $weekStart, string $weekEnd)
    {
        return $query->whereBetween('date', [$weekStart, $weekEnd]);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }
}
