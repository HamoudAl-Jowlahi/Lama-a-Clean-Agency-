<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Service */
class ServiceResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localized('name'),
            'description' => $this->localized('description'),
            'icon' => $this->icon,
            'duration_minutes' => $this->duration_minutes,
            'starting_price' => $this->whenLoaded('activePrices', fn () => $this->money($this->activePrices->min('amount'))),
            'prices' => ServicePriceResource::collection($this->whenLoaded('activePrices')),
        ];
    }
}
