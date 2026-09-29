<?php

namespace App\Models;

use App\Enums\ActorType;
use App\Enums\ComplaintMessageKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintMessage extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sender_type' => ActorType::class,
            'kind' => ComplaintMessageKind::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    /** ما يراه العميل: كل شيء عدا الملاحظات الداخلية. */
    public function scopeVisibleToCustomer(Builder $query): void
    {
        $query->where('kind', '!=', ComplaintMessageKind::InternalNote);
    }
}
