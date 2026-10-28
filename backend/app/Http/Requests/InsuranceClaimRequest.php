<?php

namespace App\Http\Requests;

use App\Models\InsuranceClaim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsuranceClaimRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'policy_id' => ['required', 'integer', 'exists:insurance_policies,id'],
            'incident_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reference_number' => [
                'required', 'string', 'max:100',
                Rule::unique('insurance_claims', 'reference_number')->ignore($this->route('claim')?->id),
            ],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::in(InsuranceClaim::STATUSES)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
