<?php

namespace App\Http\Requests;

use App\Services\DriverAvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return [
            'resource_type' => ['nullable', Rule::in(['daily', 'monthly'])],
            'full_name' => 'required|string|max:150', 'phone_number' => 'required|string|max:30',
            'driver_status' => ['required', Rule::in(app(DriverAvailabilityService::class)->statuses($this->input('resource_type') === 'monthly' ? 'monthly' : 'daily'))],
            'note' => 'nullable|string|max:20000', 'license_no' => 'prohibited',
        ];
    }
}
