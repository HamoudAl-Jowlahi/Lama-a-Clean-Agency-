<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ContractPlan */
class ContractPlanResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localized('name'),
            'description' => $this->localized('description'),
            'work_days_per_week' => $this->work_days_per_week,
            'hours_per_day' => $this->hours_per_day,
            'monthly_price' => $this->money($this->monthly_price),
            'currency' => $this->currency,
            'min_months' => max($this->min_months, (int) Settings::get('contracts.min_months')),
            'max_months' => min($this->max_months, (int) Settings::get('contracts.max_months')),
        ];
    }
}
