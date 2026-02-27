<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CandidateStage;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'job_posting_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'cv_path',
        'cv_original_name',
        'stage',
        'notes',
        'interview_date',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'stage'          => CandidateStage::class,
            'interview_date' => 'datetime',
            'rating'         => 'integer',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(
            mb_substr($this->first_name, 0, 1) . mb_substr($this->last_name, 0, 1)
        );
    }

    public function hasCv(): bool
    {
        return $this->cv_path !== null;
    }
}
