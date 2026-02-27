<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'color'      => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'description'=> ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Le nom du département est obligatoire.',
            'name.max'           => 'Le nom ne peut pas dépasser 255 caractères.',
            'color.required'     => 'La couleur est obligatoire.',
            'color.regex'        => 'La couleur doit être un code hexadécimal valide (ex: #2E86C1).',
            'manager_id.exists'  => 'Le responsable sélectionné n\'existe pas.',
            'description.max'    => 'La description ne peut pas dépasser 1 000 caractères.',
        ];
    }
}
