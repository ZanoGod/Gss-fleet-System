<?php

namespace App\Http\Requests;

use App\Models\VehicleMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->currency)) {
            $this->merge(['currency' => strtoupper(trim($this->currency))]);
        }
    }

    public function rules(): array
    {
        return [
            'car_id' => ['nullable', 'integer', 'exists:cars,id', 'required_without:monthly_car_id', 'prohibits:monthly_car_id'],
            'monthly_car_id' => ['nullable', 'integer', 'exists:monthly_cars,id', 'required_without:car_id', 'prohibits:car_id'],
            'maintenance_type' => ['required', Rule::in(VehicleMaintenance::TYPES)], 'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:20000', 'scheduled_date' => 'required|date_format:Y-m-d',
            'started_at' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'completed_at' => 'nullable|date_format:Y-m-d|after_or_equal:started_at|before_or_equal:today',
            'odometer_reading' => 'nullable|integer|min:0|max:4294967295', 'service_provider' => 'nullable|string|max:200',
            'estimated_cost' => 'nullable|numeric|min:0|max:9999999999999.99|decimal:0,2',
            'actual_cost' => 'nullable|numeric|min:0|max:9999999999999.99|decimal:0,2',
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'status' => ['required', Rule::in(VehicleMaintenance::STATUSES)], 'notes' => 'nullable|string|max:20000',
        ];
    }
}
