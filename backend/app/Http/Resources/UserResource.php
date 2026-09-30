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
            'notification_preferences' => collect(\App\Models\User::PUSH_CATEGORIES)
                ->mapWithKeys(fn (string $c) => [$c => $this->resource->wantsPush($c)]),
            'default_address_id' => $this->whenLoaded('customer', fn () => $this->customer?->default_address_id),
            // CR-3: نوع الموظف يحدد واجهة التطبيق (فريق زيارات أو خادمة بعقود)
            'worker' => $this->whenLoaded('worker', fn () => [
                'type' => $this->enum($this->worker->type),
                'team' => ($team = $this->worker->teams->first())
                    ? ['id' => $team->id, 'name' => $team->name, 'is_leader' => $team->isLeader($this->worker)]
                    : null,
            ]),
        ];
    }
}
