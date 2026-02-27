<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollExportLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_export_id',
        'user_id',
        'data',
        'is_modified',
    ];

    protected function casts(): array
    {
        return [
            'data'        => 'json',
            'is_modified' => 'boolean',
        ];
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function payrollExport(): BelongsTo
    {
        return $this->belongsTo(PayrollExport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Accesseurs données JSON ────────────────────────────────────────────────

    public function getDaysWorkedAttribute(): float
    {
        return (float) ($this->data['days_worked'] ?? 0);
    }

    public function getDaysAbsentAttribute(): float
    {
        return (float) ($this->data['days_absent'] ?? 0);
    }

    public function getOvertimeAttribute(): array
    {
        return $this->data['overtime'] ?? [
            'hours_25' => 0, 'hours_50' => 0, 'total_hours' => 0, 'compensation' => 'payment',
        ];
    }

    public function getVariablesAttribute(): array
    {
        return $this->data['variables'] ?? [];
    }

    public function getAbsencesAttribute(): array
    {
        return $this->data['absences'] ?? [];
    }
}
