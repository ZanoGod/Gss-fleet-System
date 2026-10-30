<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'guest_name' => $this->guest_name,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'customer_address' => $this->customer_address,
            'off_day' => $this->off_day,
            'service_hour' => $this->service_hour,
            'customer_status' => $this->customer_status,
            'remark' => $this->remark,
            'contract_count' => (int) $this->monthly_contracts_count,
            'active_contract_count' => (int) $this->active_contract_count,
            'latest_expiry' => $this->latest_expiry,
            'can_delete' => (int) $this->monthly_contracts_count === 0,
            'contracts' => CustomerContractResource::collection($this->whenLoaded('monthlyContracts')),
        ];
    }
}
