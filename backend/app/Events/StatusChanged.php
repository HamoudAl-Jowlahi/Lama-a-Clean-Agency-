<?php

namespace App\Events;

use App\Enums\ActorType;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * يُطلق بعد نجاح أي انتقال حالة (زيارة أو عقد). الإشعارات تستمع له في Phase 6.
 */
class StatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Model $subject,
        public readonly BackedEnum $from,
        public readonly BackedEnum $to,
        public readonly ActorType $actor,
        public readonly ?int $actorId,
    ) {}
}
