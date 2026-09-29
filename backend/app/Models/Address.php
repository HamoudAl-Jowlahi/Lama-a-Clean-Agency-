<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** نسخة ثابتة تُحفظ داخل الطلب/العقد وقت الإنشاء. */
    public function toSnapshot(): array
    {
        return $this->only(['label', 'city', 'district', 'street', 'building', 'floor', 'details', 'latitude', 'longitude']);
    }
}
