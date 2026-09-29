<?php

namespace App\Http\Resources;

use App\Enums\ComplaintStatus;
use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الشكوى كما يراها العميل. messages يجب أن تُحمّل عبر scope visibleToCustomer.
 *
 * @mixin \App\Models\Complaint
 */
class ComplaintResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->complaint_number,
            'type' => $this->type,
            'type_label' => __('api.complaint_types.'.$this->type),
            'description' => $this->description,
            'status' => $this->enum($this->status),
            'subject' => $this->booking_id
                ? ['type' => 'booking', 'id' => $this->booking_id, 'number' => $this->whenLoaded('booking', fn () => $this->booking->booking_number)]
                : ['type' => 'contract', 'id' => $this->contract_id, 'number' => $this->whenLoaded('contract', fn () => $this->contract->contract_number)],
            'messages' => $this->whenLoaded('messages', fn () => $this->messages->map(fn ($m) => [
                'id' => $m->id,
                'kind' => $this->enum($m->kind),
                'from' => $this->enum($m->sender_type),
                'body' => $m->body,
                'status_change' => $m->meta ? [
                    'from' => $this->enum(ComplaintStatus::tryFrom($m->meta['from'] ?? '')),
                    'to' => $this->enum(ComplaintStatus::tryFrom($m->meta['to'] ?? '')),
                ] : null,
                'attachments_count' => $m->relationLoaded('attachments') ? $m->attachments->count() : null,
                'at' => $this->datetime($m->created_at),
            ])),
            'created_at' => $this->datetime($this->created_at),
            'resolved_at' => $this->datetime($this->resolved_at),
        ];
    }
}
