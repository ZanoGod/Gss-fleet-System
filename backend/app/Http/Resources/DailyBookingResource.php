<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guest_company_name' => $this->guest_company_name,
            'car_id' => $this->car_id,
            'car' => $this->whenLoaded('car', fn () => $this->car),
            'custom_car_name' => $this->custom_car_name,
            'secondary_car_id' => $this->secondary_car_id,
            'secondary_car' => $this->whenLoaded('secondaryCar', fn () => $this->secondaryCar),
            'driver_id' => $this->driver_id,
            'driver' => $this->whenLoaded('driver', fn () => $this->driver),
            'custom_driver_name' => $this->custom_driver_name,
            'operator_id' => $this->operator_id,
            'operator_name' => $this->operator_name,
            'operator' => $this->whenLoaded('operator', fn () => $this->operator),
            'even_odd' => $this->even_odd,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'remark' => $this->remark,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
