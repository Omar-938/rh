<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ScheduleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isManager();
    }

    public function rules(): array
    {
        return [
            'user_id'       => ['required', 'integer', 'exists:users,id'],
            'date'          => ['required', 'date'],
            'type'          => ['required', Rule::enum(ScheduleType::class)],
            'start_time'    => ['nullable', 'date_format:H:i'],
            'end_time'      => ['nullable', 'date_format:H:i', 'after:start_time'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'    => 'Veuillez sélectionner un employé.',
            'user_id.exists'      => 'L\'employé sélectionné est introuvable.',
            'date.required'       => 'La date est obligatoire.',
            'date.date'           => 'Format de date invalide.',
            'type.required'       => 'Le type de planning est obligatoire.',
            'type.enum'           => 'Le type sélectionné est invalide.',
            'end_time.after'      => 'L\'heure de fin doit être après l\'heure de début.',
            'break_minutes.min'   => 'La pause ne peut pas être négative.',
            'break_minutes.max'   => 'La pause ne peut pas dépasser 8 heures.',
        ];
    }
}
