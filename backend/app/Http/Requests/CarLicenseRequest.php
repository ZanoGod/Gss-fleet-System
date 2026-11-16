<?php

namespace App\Http\Requests;

use App\Models\CarLicense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarLicenseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $record = $this->route('car_license');
        $id = $record instanceof CarLicense ? $record->id : null;
        $renewal = $this->route()?->getActionMethod() === 'renew';
        return [
            'car_id' => $renewal ? ['nullable', 'prohibited'] : ['nullable', 'integer', 'exists:cars,id', 'required_without:monthly_car_id', 'prohibits:monthly_car_id'],
            'monthly_car_id' => $renewal ? ['nullable', 'prohibited'] : ['nullable', 'integer', 'exists:monthly_cars,id', 'required_without:car_id', 'prohibits:car_id'],
            'license_number' => ['required', 'string', 'max:100', Rule::unique('car_licenses', 'license_number')->ignore($id)],
            'license_period' => ['required', 'integer', Rule::in(CarLicense::PERIODS)],
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'expiry_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'renewal_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issue_date', 'before_or_equal:today'],
            'renewal_fee' => ['required', 'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2'],
            'office_region' => ['required', 'string', Rule::in(array_keys(config('car_licenses.offices')))],
            'office_branch' => ['required', 'string', 'max:150', Rule::in(config('car_licenses.offices.'.$this->input('office_region'), []))],
            'remarks' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
