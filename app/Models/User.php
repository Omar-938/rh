<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'company_id',
        'department_id',
        'manager_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'avatar_path',
        'hire_date',
        'contract_type',
        'contract_end_date',
        'trial_end_date',
        'weekly_hours',
        'employee_id',
        'birth_date',
        'address',
        'city',
        'postal_code',
        'social_security_number',
        'iban',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_active',
        'last_login_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'social_security_number',
        'iban',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'role'                     => UserRole::class,
            'contract_type'            => ContractType::class,
            'hire_date'                => 'date',
            'contract_end_date'        => 'date',
            'trial_end_date'           => 'date',
            'birth_date'               => 'date',
            'last_login_at'            => 'datetime',
            'is_active'                => 'boolean',
            'weekly_hours'             => 'decimal:1',
            // Chiffrement AES-256 des données sensibles
            'social_security_number'   => 'encrypted',
            'iban'                     => 'encrypted',
        ];
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Nom complet prénom + nom.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Initiales (ex : "OB" pour Omar Ben…).
     */
    public function getInitialsAttribute(): string
    {
        $first = mb_substr($this->first_name ?? '', 0, 1);
        $last  = mb_substr($this->last_name  ?? '', 0, 1);
        return mb_strtoupper("{$first}{$last}");
    }

    /**
     * URL de l'avatar ou null.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? asset('storage/' . $this->avatar_path)
            : null;
    }

    // -------------------------------------------------------------------------
    // Helpers rôle
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    public function canApproveRequests(): bool
    {
        return $this->role?->canApproveRequests() ?? false;
    }

    public function canManageCompany(): bool
    {
        return $this->role?->canManageCompany() ?? false;
    }

    // -------------------------------------------------------------------------
    // Helpers métier
    // -------------------------------------------------------------------------

    /**
     * Met à jour la date de dernière connexion.
     */
    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }

    /**
     * Retourne les données publiques sûres pour Inertia (sans infos sensibles).
     *
     * @return array<string, mixed>
     */
    public function toInertiaArray(): array
    {
        return [
            'id'            => $this->id,
            'full_name'     => $this->full_name,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'email'         => $this->email,
            'role'          => $this->role?->value,
            'role_label'    => $this->role?->label(),
            'avatar_url'    => $this->avatar_url,
            'initials'      => $this->initials,
            'phone'         => $this->phone,
            'hire_date'     => $this->hire_date?->format('Y-m-d'),
            'contract_type' => $this->contract_type?->value,
            'weekly_hours'  => $this->weekly_hours,
            'employee_id'   => $this->employee_id,
            'department_id' => $this->department_id,
            'manager_id'    => $this->manager_id,
            'is_active'     => $this->is_active,
            'company_id'    => $this->company_id,
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Manager direct de l'employé.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Employés gérés par cet utilisateur.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    // Les autres relations (congés, pointage, documents, etc.)
    // seront ajoutées au fil des étapes suivantes.
}
