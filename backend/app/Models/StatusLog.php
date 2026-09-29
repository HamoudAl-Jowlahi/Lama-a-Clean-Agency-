<?php

namespace App\Models;

use App\Enums\ActorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** سجل غير قابل للتعديل: يُنشأ فقط، لا يُحدَّث. */
class StatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'actor_type' => ActorType::class,
            'created_at' => 'datetime',
        ];
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
