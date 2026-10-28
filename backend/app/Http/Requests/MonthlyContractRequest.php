<?php

namespace App\Http\Requests;

use App\Models\MonthlyContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonthlyContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $customerRules = $this->isMethod('POST') && $this->route('monthlyContract') === null
            ? ['required', 'integer', 'exists:customers,id']
            : ['nullable', 'integer', 'exists:customers,id'];

        return [
            'contract_no' => ['sometimes', 'required', 'string', 'max:50'],
            'customer_id' => $customerRules,
            'customer_name' => ['required', 'string', 'max:255'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'customer_address' => ['nullable', 'string'],
            'assignment_type' => ['required', Rule::in(MonthlyContract::ASSIGNMENT_TYPES)],
            'car_id' => ['nullable', 'required_if:assignment_type,Vehicle Only,Vehicle and Driver', 'integer', 'exists:monthly_cars,id'],
            'driver_id' => ['nullable', 'required_if:assignment_type,Vehicle and Driver', 'integer', 'exists:monthly_drivers,id'],
            'operator_id' => ['nullable', 'integer', 'exists:operators,id'],
            'even_odd' => ['nullable', Rule::in(['Even', 'Odd'])],
            'operation_sheet' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'monthly_rate' => ['required', 'string', 'regex:/^\d{1,10}$/'],
            'rental_currency' => ['required', Rule::in(MonthlyContract::CURRENCIES)],
            'rental_status' => ['required', Rule::in(MonthlyContract::RENTAL_STATUSES)],
            'status' => ['required', Rule::in(MonthlyContract::STATUSES)],
            'note' => ['nullable', 'string'],
            'prepared_by' => ['nullable', 'string', 'max:150'],
            'invoice_sender' => ['nullable', 'string', 'max:150'],
            'checked_by' => ['nullable', 'string', 'max:150'],
            'off_day' => ['nullable', 'string', 'max:100'],
            'service_hour' => ['nullable', 'string', 'max:100'],
            'insurance_price' => ['required', 'string', 'regex:/^\d{1,10}$/'],
            'insurance_company' => ['nullable', 'string', 'max:255'],
            'terms_conditions' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['monthly_rate', 'insurance_price'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([$field => str_replace(',', '', trim($value))]);
            } elseif (is_int($value)) {
                $this->merge([$field => (string) $value]);
            }
        }
    }
}
