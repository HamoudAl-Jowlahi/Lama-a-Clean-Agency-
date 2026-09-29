<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Payment */
class PaymentResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'amount' => $this->money($this->amount),
            'currency' => $this->currency,
            'method' => $this->enum($this->method),
            'status' => $this->enum($this->status),
            'period_start' => $this->date($this->period_start),
            'period_end' => $this->date($this->period_end),
            'due_date' => $this->date($this->due_date),
            'collected_at' => $this->datetime($this->collected_at),
        ];
    }
}
