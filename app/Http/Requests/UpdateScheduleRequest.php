<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ScheduleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isManager();
    }

    public function rules(): array
    {
        return [
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
            'type.required'  => 'Le type de planning est obligatoire.',
            'type.enum'      => 'Le type sélectionné est invalide.',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ];
    }
}
