<?php

namespace App\Http\Resources;

use App\Models\MonthlyCar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FleetVehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $monthly = $this->resource instanceof MonthlyCar;
        $assignments = $monthly && $this->relationLoaded('contracts') ? $this->contracts : collect();
        if (! $monthly) {
            foreach (['bookings', 'secondaryBookings'] as $relation) {
                if ($this->relationLoaded($relation)) {
                    $assignments = $assignments->merge($this->{$relation});
                }
            }
        }

        return array_merge($this->resource->only(['id', 'car_type', 'plate_no', 'model_name', 'seat_capacity', 'availability_status', 'note', 'created_at', 'updated_at']), [
            'resource_type' => $monthly ? 'monthly' : 'daily',
            'effective_status' => $this->resource->effectiveAvailability(),
            'assignments' => $assignments->unique('id')->values()->map(fn ($a) => [
                'id' => $a->id, 'name' => $monthly ? $a->customer_name : $a->guest_company_name,
                'start_date' => $a->start_date?->format('Y-m-d'), 'end_date' => $a->end_date?->format('Y-m-d'), 'status' => $a->status,
                'driver' => $a->driver?->full_name, 'driver_id' => $a->driver_id,
            ]),
            'maintenances' => $this->whenLoaded('maintenances'),
            'insurance_policies' => $this->whenLoaded('insurancePolicies'),
        ]);
    }
}
