<?php
namespace App\Http\Resources;

use App\Models\MonthlyDriver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $monthly = $this->resource instanceof MonthlyDriver;
        $relation = $monthly ? 'contracts' : 'bookings';
        $data = $this->resource->only(['id', 'full_name', 'phone_number', 'license_no', 'driver_status', 'note', 'created_at', 'updated_at']);
        $data['resource_type'] = $monthly ? 'monthly' : 'daily';
        $data['assignments'] = $this->relationLoaded($relation) ? $this->{$relation}->map(fn ($a) => [
            'id' => $a->id, 'resource_type' => $data['resource_type'], 'name' => $monthly ? $a->customer_name : $a->guest_company_name,
            'start_date' => $a->start_date?->format('Y-m-d'), 'end_date' => $a->end_date?->format('Y-m-d'), 'status' => $a->status,
            'vehicle' => $a->car?->plate_no ?: ($monthly ? null : $a->custom_car_name),
            'car_id' => $a->car_id, 'secondary_vehicle' => $monthly ? null : $a->secondaryCar?->plate_no,
        ]) : [];
        return $data;
    }
}
