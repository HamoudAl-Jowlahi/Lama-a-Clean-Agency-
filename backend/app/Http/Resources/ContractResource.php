<?php

namespace App\Http\Resources;

use App\Enums\ContractStatus;
use App\Http\Resources\Concerns\FormatsValues;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * العقد كما يراه العميل صاحبه.
 *
 * @mixin \App\Models\Contract
 */
class ContractResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->contract_number,
            'status' => $this->enum($this->status),
            'plan' => [
                'name' => $this->localizedSnapshot($this->plan_snapshot, 'name'),
                'work_days_per_week' => $this->plan_snapshot['work_days_per_week'] ?? null,
                'hours_per_day' => $this->plan_snapshot['hours_per_day'] ?? null,
            ],
            'start_date' => $this->date($this->start_date),
            'end_date' => $this->date($this->end_date),
            'months' => $this->months,
            'monthly_price' => $this->money($this->monthly_price),
            'total_amount' => $this->money($this->total_amount),
            'currency' => $this->currency,
            'payment_method' => 'cash',
            'address' => $this->address_snapshot,
            'customer_notes' => $this->customer_notes,
            'progress' => $this->progress(),
            'current_worker' => $this->whenLoaded('currentAssignment', fn () => $this->currentAssignment ? [
                'id' => $this->currentAssignment->worker_id,
                'name' => $this->firstName($this->currentAssignment->worker->user->name),
                'since' => $this->date($this->currentAssignment->started_on),
            ] : null),
            'workers_history' => $this->whenLoaded('assignments', fn () => $this->assignments->map(fn ($a) => [
                'worker_id' => $a->worker_id,
                'name' => $this->firstName($a->worker->user->name),
                'from' => $this->date($a->started_on),
                'to' => $this->date($a->ended_on),
                'end_reason' => $this->enum($a->end_reason),
            ])),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'change_requests' => ChangeRequestResource::collection($this->whenLoaded('changeRequests')),
            'timeline' => $this->whenLoaded('statusLogs', fn () => $this->timeline($this->statusLogs, ContractStatus::class)),
            'terminated_at' => $this->status === ContractStatus::Terminated ? $this->date($this->ended_at) : null,
            'created_at' => $this->datetime($this->created_at),
        ];
    }

    /** "اليوم 12 من 30" لشاشة عقدي. */
    private function progress(): ?array
    {
        if ($this->status !== ContractStatus::Active) {
            return null;
        }

        $today = CarbonImmutable::parse(CarbonImmutable::now(Settings::get('timezone'))->toDateString());
        $start = CarbonImmutable::parse($this->start_date);
        $total = (int) $start->diffInDays(CarbonImmutable::parse($this->end_date)) + 1;

        return ['day' => min($total, (int) $start->diffInDays($today) + 1), 'total_days' => $total];
    }
}
