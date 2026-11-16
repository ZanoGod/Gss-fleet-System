<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarLicenseDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return ['document' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png']];
    }
}
