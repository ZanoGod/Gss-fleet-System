<?php

namespace App\Http\Requests;

use App\Models\DailyBooking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'guest_company_name' => ['required', 'string', 'max:255'],
            'car_id' => ['nullable', 'integer', 'exists:cars,id'],
            'custom_car_name' => ['nullable', 'string', 'max:255'],
            'secondary_car_id' => ['nullable', 'integer', 'different:car_id', 'exists:cars,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'custom_driver_name' => ['nullable', 'string', 'max:255'],
            'operator_id' => ['required', 'integer', 'exists:operators,id'],
            'even_odd' => ['required', Rule::in(['Even', 'Odd'])],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(DailyBooking::STATUSES)],
            'remark' => ['nullable', 'string'],
        ];
    }
}
