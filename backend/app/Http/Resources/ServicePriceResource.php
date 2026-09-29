<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ServicePrice */
class ServicePriceResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->localized('label'),
            'unit' => $this->enum($this->unit),
            'amount' => $this->money($this->amount),
            'currency' => $this->currency,
        ];
    }
}
