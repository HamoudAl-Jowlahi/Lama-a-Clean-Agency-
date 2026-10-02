<?php

namespace App\Http\Resources;

use App\Enums\ContractStatus;
use App\Http\Resources\Concerns\FormatsValues;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * العقد كما تراه عاملة عملت/تعمل فيه. بيانات العميل (العنوان، الهاتف، الملاحظات)
 * تظهر فقط خلال فترة إسنادها الحالية — بعد انتهائها تُخفى.
 *
 * يجب تحميل: customer.user و assignments.
 *
 * @mixin \App\Models\Contract
 */
class WorkerContractResource extends JsonResource
{
    use FormatsValues;

    private ?Worker $worker = null;

    public function forWorker(Worker $worker): static
    {
        $this->worker = $worker;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $worker = $this->worker ?? $request->user()->worker;
        $mine = $this->assignments->where('worker_id', $worker->id)->sortByDesc('id')->first();
        $current = $mine && $mine->ended_on === null
            && in_array($this->status, [ContractStatus::Assigned, ContractStatus::Active], true);
        $user = $this->customer->user;

        return [
            'id' => $this->id,
            'number' => $this->contract_number,
            'status' => $this->enum($this->status),
            'is_current' => $current,
            'plan' => [
                'name' => $this->localizedSnapshot($this->plan_snapshot, 'name'),
                'work_days_per_week' => $this->plan_snapshot['work_days_per_week'] ?? null,
                'hours_per_day' => $this->plan_snapshot['hours_per_day'] ?? null,
            ],
            'my_period' => [
                'from' => $this->date($mine?->started_on),
                'to' => $this->date($mine?->ended_on ?? $this->end_date),
            ],
            'customer' => [
                'name' => $this->firstName($user->name),
                'phone' => $current ? $user->phone : null,
            ],
            'address' => $current ? $this->address_snapshot : null,
            'customer_notes' => $current ? $this->customer_notes : null,
        ];
    }
}
