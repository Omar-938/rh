<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return [
            'date'         => ['required', 'date', 'before_or_equal:today'],
            'hours'        => ['required', 'numeric', 'min:0.25', 'max:12'],
            'rate'         => ['required', 'in:25,50'],
            'reason'       => ['required', 'string', 'min:5', 'max:1000'],
            'compensation' => ['required', 'in:payment,rest'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'hours.min'            => 'La durée minimale est de 15 minutes (0.25h).',
            'hours.max'            => 'La durée maximale par déclaration est de 12 heures.',
            'reason.min'           => 'Veuillez décrire le motif (au moins 5 caractères).',
        ];
    }
}
