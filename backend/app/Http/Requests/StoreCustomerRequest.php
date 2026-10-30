<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Customer::class);
    }

    protected function prepareForValidation(): void
    {
        $fields = ['company_name', 'guest_name', 'contact_person', 'contact_phone', 'contact_email', 'customer_address', 'off_day', 'service_hour', 'remark'];
        $trimmed = [];
        foreach ($fields as $field) {
            if (is_string($this->input($field))) {
                $trimmed[$field] = trim($this->input($field));
            }
        }
        $this->merge($trimmed);
    }

    public function rules(): array
    {
        $unique = Rule::unique('customers', 'company_name');
        if ($customer = $this->route('customer')) {
            $unique->ignore($customer->id);
        }

        return [
            'company_name' => ['required', 'string', 'max:255', $unique],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'customer_address' => ['nullable', 'string'],
            'off_day' => ['nullable', 'string', 'max:50'],
            'service_hour' => ['required', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
            'customer_status' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.unique' => 'Company already exists.',
            'service_hour.required' => 'Service Hour is required.',
            'customer_status.prohibited' => 'Customer status is managed by contract activity.',
        ];
    }
}
