<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Contrôle fin dans le controller
    }

    public function rules(): array
    {
        $categories = array_column(DocumentCategory::cases(), 'value');

        return [
            'file'               => [
                'required',
                'file',
                'max:20480',         // 20 Mo
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,png,jpg,jpeg,webp,zip',
            ],
            'name'               => ['required', 'string', 'max:255'],
            'category'           => ['required', 'string', new Enum(DocumentCategory::class)],
            'user_id'            => ['nullable', 'integer', 'exists:users,id'],
            'requires_signature' => ['boolean'],
            'expires_at'         => ['nullable', 'date', 'after:today'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'      => 'Veuillez sélectionner un fichier.',
            'file.max'           => 'Le fichier ne doit pas dépasser 20 Mo.',
            'file.mimes'         => 'Format non supporté. Formats acceptés : PDF, Word, Excel, images, ZIP.',
            'name.required'      => 'Le nom du document est obligatoire.',
            'category.required'  => 'Veuillez choisir une catégorie.',
            'expires_at.after'   => 'La date d\'expiration doit être dans le futur.',
        ];
    }
}
