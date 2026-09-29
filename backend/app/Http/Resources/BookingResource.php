<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الزيارة كما يراها العميل صاحبها.
 *
 * @mixin \App\Models\Booking
 */
class BookingResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->booking_number,
            'status' => $this->enum($this->status),
            'scheduled_date' => $this->date($this->scheduled_date),
            'scheduled_time' => $this->time($this->scheduled_time),
            'address' => $this->address_snapshot,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'service' => $i->service_name_snapshot,
                'option' => $i->price_label_snapshot,
                'quantity' => $i->quantity,
                'unit_price' => $this->money($i->unit_price),
                'total' => $this->money($i->line_total),
            ])),
            'subtotal' => $this->money($this->subtotal),
            'tax' => $this->money($this->tax),
            'total' => $this->money($this->total),
            'currency' => $this->currency,
            'payment_method' => 'cash',
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? new PaymentResource($this->payment) : null),
            'customer_notes' => $this->customer_notes,
            // CR-3: الزيارة ينفذها فريق
            'team' => $this->whenLoaded('activeAssignment', fn () => $this->activeAssignment?->relationLoaded('team')
                ? ['name' => $this->activeAssignment->team->name]
                : null),
            'timeline' => $this->whenLoaded('statusLogs', fn () => $this->timeline($this->statusLogs, BookingStatus::class)),
            'rating' => $this->whenLoaded('rating', fn () => $this->rating ? new RatingResource($this->rating) : null),
            // when() وليس whenLoaded(): الأخيرة ترجع null دون استدعاء الدالة إذا لم يوجد تقييم
            'can_rate' => $this->when($this->relationLoaded('rating'), fn () => $this->status === BookingStatus::Completed && ! $this->rating),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->datetime($this->created_at),
        ];
    }
}
