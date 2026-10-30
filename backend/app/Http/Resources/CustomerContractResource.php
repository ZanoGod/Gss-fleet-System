<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_no' => $this->contract_no,
            'guest_name' => $this->guest_name,
            'contact_person' => $this->contact_person,
            'assignment_type' => $this->assignment_type,
            'rental_status' => $this->rental_status,
            'status' => $this->status,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'car_type' => $this->car?->car_type,
            'plate_no' => $this->car?->plate_no,
            'driver_name' => $this->driver?->full_name,
            'operator_name' => $this->operator?->full_name,
        ];
    }
}
