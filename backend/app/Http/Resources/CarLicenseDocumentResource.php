<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarLicenseDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'car_license_id' => $this->car_license_id,
            'original_name' => $this->original_name, 'mime_type' => $this->mime_type,
            'size' => $this->size, 'created_at' => $this->created_at,
            'download_url' => route('car-licenses.documents.download', ['document' => $this->id]),
        ];
    }
}
