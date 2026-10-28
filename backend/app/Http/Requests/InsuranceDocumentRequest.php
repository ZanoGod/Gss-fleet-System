<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsuranceDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'document' => [
                'required', 'file', 'max:'.config('insurance.document_max_kb', 10240),
                'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png',
            ],
            'replaces_id' => ['nullable', 'integer', 'exists:insurance_documents,id'],
        ];
    }
}
