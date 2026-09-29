<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ContractChangeRequest */
class ChangeRequestResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->request_number,
            'type' => $this->enum($this->type),
            'reason_type' => $this->reason_type,
            'details' => $this->details,
            'requested_date' => $this->date($this->requested_date),
            'status' => $this->enum($this->status),
            'admin_response' => $this->admin_response,
            'handled_at' => $this->datetime($this->handled_at),
            'attachments_count' => $this->whenCounted('attachments'),
            'created_at' => $this->datetime($this->created_at),
        ];
    }
}
