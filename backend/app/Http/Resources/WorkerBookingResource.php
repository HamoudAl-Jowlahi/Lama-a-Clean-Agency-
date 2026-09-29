<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Enums\BookingStatus;
use App\Http\Resources\Concerns\FormatsValues;
use App\StateMachines\BookingStateMachine;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الزيارة كما تراها العاملة المسندة إليها: فقط ما يلزم للتنفيذ.
 * رقم هاتف العميل يظهر فقط أثناء التنفيذ (من booking.show_customer_phone_from_status).
 *
 * @mixin \App\Models\Booking
 */
class WorkerBookingResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        $user = $this->customer->user;

        return [
            'id' => $this->id,
            'number' => $this->booking_number,
            'status' => $this->enum($this->status),
            'assignment_status' => $this->whenLoaded('activeAssignment', fn () => $this->enum($this->activeAssignment?->status)),
            'scheduled_date' => $this->date($this->scheduled_date),
            'scheduled_time' => $this->time($this->scheduled_time),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'service' => $i->service_name_snapshot,
                'option' => $i->price_label_snapshot,
                'quantity' => $i->quantity,
            ])),
            'address' => $this->address_snapshot,
            'customer' => [
                'name' => $this->firstName($user->name),
                'phone' => $this->phoneVisible() ? $user->phone : null,
            ],
            'customer_notes' => $this->customer_notes,
            // المبلغ الذي يُحصّل نقداً عند الإتمام
            'amount_to_collect' => $this->money($this->total),
            'currency' => $this->currency,
            'next_statuses' => collect(app(BookingStateMachine::class)->nextFor($this->resource, ActorType::Worker))
                ->reject(fn (BookingStatus $s) => $s === BookingStatus::Confirmed) // الرجوع = رفض الإسناد (مسار مستقل)
                ->map(fn (BookingStatus $s) => $this->enum($s))
                ->values(),
        ];
    }

    private function phoneVisible(): bool
    {
        $flow = ['pending', 'confirmed', 'assigned', 'on_the_way', 'in_progress'];
        $from = array_search(Settings::get('booking.show_customer_phone_from_status'), $flow, true);
        $now = array_search($this->status->value, $flow, true);

        return $from !== false && $now !== false && $now >= $from;
    }
}
