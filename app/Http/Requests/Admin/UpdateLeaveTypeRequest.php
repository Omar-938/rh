<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AcquisitionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'color'                => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon'                 => ['nullable', 'string', 'max:10'],
            'acquisition_type'     => ['required', Rule::enum(AcquisitionType::class)],
            'days_per_year'        => ['required', 'numeric', 'min:0', 'max:365'],
            'requires_approval'    => ['boolean'],
            'is_paid'              => ['boolean'],
            'is_active'            => ['boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notice_days'          => ['required', 'integer', 'min:0', 'max:365'],
            'requires_attachment'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'Le nom du type de congé est obligatoire.',
            'color.required'            => 'La couleur est obligatoire.',
            'color.regex'               => 'La couleur doit être un code hexadécimal valide (ex: #2E86C1).',
            'acquisition_type.required' => 'Le mode d\'acquisition est obligatoire.',
            'acquisition_type.enum'     => 'Le mode d\'acquisition sélectionné est invalide.',
            'days_per_year.required'    => 'Le nombre de jours par an est obligatoire.',
            'days_per_year.min'         => 'Le nombre de jours doit être positif ou nul.',
            'days_per_year.max'         => 'Le nombre de jours ne peut pas dépasser 365.',
            'notice_days.required'      => 'Le délai de prévenance est obligatoire.',
            'notice_days.min'           => 'Le délai de prévenance doit être positif ou nul.',
            'max_consecutive_days.min'  => 'La limite doit être d\'au moins 1 jour.',
        ];
    }
}
