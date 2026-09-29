<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** باقة عقد الاستئجار (مثال: دوام كامل — 6 أيام × 8 ساعات). */
class ContractPlan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'plan_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /** نسخة ثابتة تُحفظ داخل العقد وقت الإنشاء. */
    public function toSnapshot(): array
    {
        return $this->only(['name_ar', 'name_en', 'work_days_per_week', 'hours_per_day', 'monthly_price', 'currency']);
    }
}
