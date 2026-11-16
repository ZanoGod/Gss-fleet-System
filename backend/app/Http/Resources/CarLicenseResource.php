<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarLicenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'car_id' => $this->car_id, 'monthly_car_id' => $this->monthly_car_id,
            'vehicle' => $this->vehicle, 'vehicle_label' => $this->vehicle ? $this->vehicle['plate_no'].' · '.$this->vehicle['model_name'].' ('.$this->vehicle['fleet'].')' : null,
            'license_number' => $this->license_number, 'license_period' => $this->license_period,
            'issue_date' => $this->issue_date?->format('Y-m-d'), 'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'renewal_date' => $this->renewal_date?->format('Y-m-d'), 'renewal_fee' => $this->renewal_fee,
            'office_region' => $this->office_region, 'office_branch' => $this->office_branch,
            'remarks' => $this->remarks, 'status' => $this->status, 'days_until_expiry' => $this->days_until_expiry,
            'is_current' => $this->relationLoaded('renewal') ? ! $this->renewal : ! $this->renewal()->exists(), 'renewal_parent_id' => $this->renewal_parent_id,
            'documents' => CarLicenseDocumentResource::collection($this->whenLoaded('documents')),
            'renewal_history' => $this->whenLoaded('historyRecords', fn () => $this->historyRecords->map(fn ($record) => [
                'id' => $record->id, 'license_number' => $record->license_number, 'issue_date' => $record->issue_date?->format('Y-m-d'),
                'expiry_date' => $record->expiry_date?->format('Y-m-d'), 'renewal_date' => $record->renewal_date?->format('Y-m-d'),
                'renewal_fee' => $record->renewal_fee, 'status' => $record->status,
            ])->values()),
        ];
    }
}
