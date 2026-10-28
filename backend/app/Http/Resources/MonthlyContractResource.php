<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_no' => $this->contract_no,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer),
            'customer_name' => $this->customer_name,
            'guest_name' => $this->guest_name,
            'contact_person' => $this->contact_person,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'customer_address' => $this->customer_address,
            'assignment_type' => $this->assignment_type,
            'car_id' => $this->car_id,
            'car' => $this->whenLoaded('car', fn () => $this->car),
            'driver_id' => $this->driver_id,
            'driver' => $this->whenLoaded('driver', fn () => $this->driver),
            'operator_id' => $this->operator_id,
            'operator_name' => $this->operator_name,
            'operator' => $this->whenLoaded('operator', fn () => $this->operator),
            'even_odd' => $this->even_odd,
            'operation_sheet' => $this->operation_sheet,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'monthly_rate' => $this->monthly_rate,
            'rental_currency' => $this->rental_currency,
            'rental_status' => $this->rental_status,
            'status' => $this->status,
            'renewal_parent_id' => $this->renewal_parent_id,
            'renewal_parent' => $this->whenLoaded('renewalParent', fn () => $this->renewalParent),
            'renewals' => $this->whenLoaded('renewals', fn () => $this->renewals),
            'note' => $this->note,
            'prepared_by' => $this->prepared_by,
            'invoice_sender' => $this->invoice_sender,
            'checked_by' => $this->checked_by,
            'off_day' => $this->off_day,
            'service_hour' => $this->service_hour,
            'insurance_price' => $this->insurance_price,
            'insurance_company' => $this->insurance_company,
            'terms_conditions' => $this->terms_conditions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
