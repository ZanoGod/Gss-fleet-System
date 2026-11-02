<?php

namespace App\Http\Resources;

use App\Models\DailyBooking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $vehicle = $this->car_id ? $this->car : $this->monthlyCar;
        $data['resource_type'] = $this->car_id ? 'daily' : 'monthly';
        $data['vehicle_label'] = $vehicle ? $vehicle->plate_no.' · '.$vehicle->model_name.' ('.$data['resource_type'].')' : null;
        foreach (['scheduled_date', 'started_at', 'completed_at'] as $date) {
            $data[$date] = $this->{$date}?->format('Y-m-d');
        }
        if ($vehicle && ! $request->routeIs('maintenances.index')) {
            $monthly = (bool) $this->monthly_car_id;
            $query = $monthly ? $vehicle->contracts() : DailyBooking::query()->where(fn ($q) => $q->where('car_id', $vehicle->id)->orWhere('secondary_car_id', $vehicle->id));
            $data['assignments'] = $query->with('driver')->where('status', '!=', 'Cancelled')->whereDate('start_date', '<=', $this->scheduled_date)->whereDate('end_date', '>=', $this->scheduled_date)->orderBy('start_date')->get()->map(fn ($a) => [
                'id' => $a->id, 'resource_type' => $monthly ? 'monthly' : 'daily', 'name' => $monthly ? $a->customer_name : $a->guest_company_name,
                'start_date' => $a->start_date?->format('Y-m-d'), 'end_date' => $a->end_date?->format('Y-m-d'), 'status' => $a->status,
                'vehicle' => $vehicle->plate_no, 'driver' => $a->driver?->full_name, 'driver_id' => $a->driver_id,
            ]);
        }

        return $data;
    }
}
