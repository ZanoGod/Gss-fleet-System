<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Models\MonthlyContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(Customer::STATUSES)],
            'assignment_type' => ['nullable', Rule::in(MonthlyContract::ASSIGNMENT_TYPES)],
            'rental_status' => ['nullable', Rule::in(MonthlyContract::RENTAL_STATUSES)],
            'expiry' => ['nullable', Rule::in(['expiring', 'expired'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
