<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'policy_id' => $this->policy_id,
            'policy' => $this->whenLoaded('policy', fn () => [
                'id' => $this->policy?->id,
                'policy_number' => $this->policy?->policy_number,
                'status' => $this->policy?->status,
                'currency' => $this->policy?->currency,
                'provider' => $this->policy?->provider ? [
                    'id' => $this->policy->provider->id,
                    'name' => $this->policy->provider->name,
                ] : null,
            ]),
            'incident_date' => $this->incident_date?->format('Y-m-d'),
            'reference_number' => $this->reference_number,
            'amount' => $this->amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'events' => InsuranceClaimEventResource::collection($this->whenLoaded('events')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
