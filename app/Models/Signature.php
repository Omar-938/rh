<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SignatureStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'token',
        'status',
        'signature_type',
        'signature_data',
        'ip_address',
        'user_agent',
        'document_hash',
        'signed_at',
        'declined_reason',
        'declined_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status'      => SignatureStatus::class,
            'signed_at'   => 'datetime',
            'declined_at' => 'datetime',
            'expires_at'  => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === SignatureStatus::Pending;
    }

    public function isSigned(): bool
    {
        return $this->status === SignatureStatus::Signed;
    }

    public function isExpired(): bool
    {
        if ($this->status === SignatureStatus::Expired) {
            return true;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActionable(): bool
    {
        return $this->isPending() && ! $this->isExpired();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query): mixed
    {
        return $query->where('status', SignatureStatus::Pending->value);
    }

    public function scopeSigned($query): mixed
    {
        return $query->where('status', SignatureStatus::Signed->value);
    }
}
