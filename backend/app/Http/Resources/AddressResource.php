<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Address */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'city' => $this->city,
            'district' => $this->district,
            'street' => $this->street,
            'building' => $this->building,
            'floor' => $this->floor,
            'details' => $this->details,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
