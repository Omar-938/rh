<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'leave_type_id'    => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'       => ['required', 'date', 'after_or_equal:today'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            'start_half'       => ['nullable', 'string', 'in:morning,afternoon'],
            'end_half'         => ['nullable', 'string', 'in:morning,afternoon'],
            'employee_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required'   => 'Veuillez sélectionner un type de congé.',
            'leave_type_id.exists'     => 'Le type de congé sélectionné est invalide.',
            'start_date.required'      => 'La date de début est obligatoire.',
            'start_date.after_or_equal'=> 'La date de début ne peut pas être dans le passé.',
            'end_date.required'        => 'La date de fin est obligatoire.',
            'end_date.after_or_equal'  => 'La date de fin doit être égale ou postérieure à la date de début.',
            'start_half.in'            => 'La demi-journée de début est invalide.',
            'end_half.in'              => 'La demi-journée de fin est invalide.',
            'employee_comment.max'     => 'Le commentaire ne peut pas dépasser 1 000 caractères.',
        ];
    }
}
