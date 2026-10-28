<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'car_id' => $this->car_id,
            'monthly_car_id' => $this->monthly_car_id,
            'vehicle' => $this->vehicle,
            'provider_id' => $this->provider_id,
            'provider' => $this->whenLoaded('provider', fn () => new InsuranceProviderResource($this->provider)),
            'policy_number' => $this->policy_number,
            'insurance_type' => $this->insurance_type,
            'coverage_details' => $this->coverage_details,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'expiration_date' => $this->expiration_date?->format('Y-m-d'),
            'premium_amount' => $this->premium_amount,
            'currency' => $this->currency,
            'coverage_amount' => $this->coverage_amount,
            'notes' => $this->notes,
            'status' => $this->status,
            'renewal_parent_id' => $this->renewal_parent_id,
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'policy_number' => $this->parent->policy_number,
                'expiration_date' => $this->parent->expiration_date?->format('Y-m-d'),
            ] : null),
            'renewal' => $this->whenLoaded('renewal', fn () => $this->renewal ? [
                'id' => $this->renewal->id,
                'policy_number' => $this->renewal->policy_number,
                'start_date' => $this->renewal->start_date?->format('Y-m-d'),
                'expiration_date' => $this->renewal->expiration_date?->format('Y-m-d'),
            ] : null),
            'claims' => InsuranceClaimResource::collection($this->whenLoaded('claims')),
            'documents' => InsuranceDocumentResource::collection($this->whenLoaded('documents')),
            'cancelled_at' => $this->cancelled_at,
            'archived_at' => $this->archived_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
