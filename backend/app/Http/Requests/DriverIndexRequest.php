<?php

namespace App\Http\Requests;

use App\Services\DriverAvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $scope = $this->input('resource_type') === 'monthly' ? 'monthly' : 'daily';

        return [
            'resource_type' => ['nullable', Rule::in(['daily', 'monthly'])],
            'search' => 'nullable|string|max:500', 'month' => 'nullable|date_format:Y-m',
            'status' => ['nullable', Rule::in(app(DriverAvailabilityService::class)->statuses($scope))],
            'date_from' => 'nullable|required_with:date_to|date_format:Y-m-d',
            'date_to' => 'nullable|required_with:date_from|date_format:Y-m-d|after_or_equal:date_from',
            'except_id' => 'nullable|integer|min:1',
        ];
    }
}
