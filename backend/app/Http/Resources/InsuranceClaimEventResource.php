<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceClaimEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount' => $this->amount,
            'notes' => $this->notes,
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor?->id,
                'username' => $this->actor?->username,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
