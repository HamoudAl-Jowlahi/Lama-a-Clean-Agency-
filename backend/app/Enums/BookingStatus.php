<?php

namespace App\Enums;

/**
 * حالات الزيارة. قواعد الانتقال بينها في BookingStateMachine (Phase 3).
 */
enum BookingStatus: string
{
    use EnumHelpers;

    public const LANG_KEY = 'booking_status';

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Assigned = 'assigned';
    case OnTheWay = 'on_the_way';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Rejected], true);
    }

    /** الحالات التي تشغل وقت العاملة (للتحقق من التعارض). */
    public static function occupyingWorker(): array
    {
        return [self::Assigned, self::OnTheWay, self::InProgress];
    }
}
