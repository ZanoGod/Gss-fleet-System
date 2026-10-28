<?php

namespace App\Http\Requests;

use App\Models\InsurancePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    protected function prepareForValidation(): void
    {
        $type = $this->input('insurance_type');
        if (is_string($type)) {
            $this->merge(['insurance_type' => mb_strtolower(trim($type))]);
        }
    }

    public function rules(): array
    {
        return [
            'car_id' => ['nullable', 'integer', 'exists:cars,id'],
            'monthly_car_id' => ['nullable', 'integer', 'exists:monthly_cars,id'],
            'provider_id' => ['required', 'integer', 'exists:insurance_providers,id'],
            'policy_number' => [
                'required', 'string', 'max:100',
                Rule::unique('insurance_policies', 'policy_number')
                    ->where(fn ($query) => $query->where('provider_id', $this->input('provider_id'))->where('start_date', $this->input('start_date')))
                    ->ignore($this->route('policy')?->id),
            ],
            'insurance_type' => ['required', 'string', 'max:80'],
            'coverage_details' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'expiration_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'premium_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'coverage_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'currency' => ['required', Rule::in(InsurancePolicy::CURRENCIES)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasDaily = filled($this->input('car_id'));
            $hasMonthly = filled($this->input('monthly_car_id'));
            if ($hasDaily === $hasMonthly) {
                $validator->errors()->add('car_id', 'Choose exactly one daily or monthly fleet vehicle.');
            }
        });
    }
}
