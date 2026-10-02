<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Enums\AssignmentStatus;
use App\Enums\BookingStatus;
use App\Http\Resources\Concerns\FormatsValues;
use App\Models\Worker;
use App\StateMachines\BookingStateMachine;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الزيارة كما يراها عضو الفريق المسند (CR-3): فقط ما يلزم للتنفيذ.
 * القائد وحده: يرى هاتف العميل أثناء التنفيذ، والخطوات التالية، والمبلغ المطلوب تحصيله.
 *
 * يجب تحميل: items، customer.user، activeAssignment.team.
 *
 * @mixin \App\Models\Booking
 */
class WorkerBookingResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        /** @var Worker $worker */
        $worker = $request->user()->worker;
        $assignment = $this->activeAssignment;
        $isLeader = $assignment && $assignment->team->isLeader($worker);
        $user = $this->customer->user;

        return [
            'id' => $this->id,
            'number' => $this->booking_number,
            'status' => $this->enum($this->status),
            'team' => $assignment ? ['id' => $assignment->team->id, 'name' => $assignment->team->name] : null,
            'is_leader' => $isLeader,
            'assignment_status' => $this->enum($assignment?->status),
            'scheduled_date' => $this->date($this->scheduled_date),
            'scheduled_time' => $this->time($this->scheduled_time),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => $this->itemNames($i) + [
                'quantity' => $i->quantity,
            ])),
            'address' => $this->address_snapshot,
            'customer' => [
                'name' => $this->firstName($user->name),
                'phone' => $isLeader && $this->phoneVisible() ? $user->phone : null,
            ],
            'customer_notes' => $this->customer_notes,
            // المبلغ الذي يحصّله القائد نقداً عند الإتمام
            'amount_to_collect' => $isLeader ? $this->money($this->total) : null,
            'currency' => $this->currency,
            // قبل القبول، أو لغير القائد: لا خطوات — التطبيق يعرض "قبول / رفض" للقائد فقط
            'next_statuses' => $isLeader && $assignment->status === AssignmentStatus::Accepted
                ? collect(app(BookingStateMachine::class)->nextFor($this->resource, ActorType::Worker))
                    ->reject(fn (BookingStatus $s) => $s === BookingStatus::Confirmed) // الرجوع = رفض الإسناد (مسار مستقل)
                    ->map(fn (BookingStatus $s) => $this->enum($s))
                    ->values()
                : [],
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
