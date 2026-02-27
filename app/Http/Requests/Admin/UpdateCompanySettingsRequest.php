<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section' => ['required', 'string', 'in:general,branding,hr,accountant'],

            // ── Section général ──────────────────────────────────────────────
            'name'        => ['required_if:section,general', 'nullable', 'string', 'max:255'],
            'siret'       => ['nullable', 'string', 'regex:/^[0-9]{14}$/'],
            'address'     => ['nullable', 'string', 'max:500'],
            'city'        => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone'       => ['nullable', 'string', 'max:20'],

            // ── Section branding ─────────────────────────────────────────────
            'logo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // ── Section RH ───────────────────────────────────────────────────
            'work_hours_per_day' => ['nullable', 'numeric', 'min:1', 'max:24'],
            'work_days_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'timezone'           => ['nullable', 'string', 'timezone:all'],

            // ── Section comptabilité ─────────────────────────────────────────
            'accountant_emails'   => ['nullable', 'array', 'max:10'],
            'accountant_emails.*' => ['email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required_if'        => "Le nom de l'entreprise est obligatoire.",
            'siret.regex'             => 'Le SIRET doit contenir exactement 14 chiffres.',
            'logo.image'              => 'Le fichier doit être une image.',
            'logo.mimes'              => 'Le logo doit être un fichier JPG, PNG ou WebP.',
            'logo.max'                => 'Le logo ne doit pas dépasser 2 Mo.',
            'primary_color.regex'     => 'La couleur doit être au format hexadécimal (#RRGGBB).',
            'timezone.timezone'       => "Le fuseau horaire n'est pas valide.",
            'accountant_emails.max'   => 'Maximum 10 adresses email comptable.',
            'accountant_emails.*.email' => 'Une des adresses email est invalide.',
        ];
    }
}
