<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->enum($this->role),
            'locale' => $this->locale,
            'default_address_id' => $this->whenLoaded('customer', fn () => $this->customer?->default_address_id),
        ];
    }
}
