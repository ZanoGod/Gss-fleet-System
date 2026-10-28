<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'policy_id' => $this->policy_id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'archived_at' => $this->archived_at,
            'replaces_id' => $this->replaces_id,
            'download_url' => route('insurance.documents.download', ['document' => $this->id]),
            'created_at' => $this->created_at,
        ];
    }
}
