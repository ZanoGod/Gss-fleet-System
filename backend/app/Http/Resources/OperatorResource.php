<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignments = collect();
        foreach (['bookings' => 'daily', 'monthlyContracts' => 'monthly'] as $relation => $type) {
            if ($this->relationLoaded($relation)) $assignments = $assignments->merge($this->{$relation}->map(fn ($a) => ['id' => $a->id, 'resource_type' => $type, 'name' => $type === 'daily' ? $a->guest_company_name : $a->customer_name, 'start_date' => $a->start_date?->format('Y-m-d'), 'end_date' => $a->end_date?->format('Y-m-d'), 'status' => $a->status]));
        }
        return array_merge($this->resource->only(['id', 'full_name', 'phone_number', 'operator_status', 'note', 'created_at', 'updated_at']), ['assignments' => $assignments]);
    }
}
