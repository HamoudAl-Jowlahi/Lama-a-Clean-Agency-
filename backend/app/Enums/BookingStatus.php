<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * حالات الزيارة. قواعد الانتقال بينها في BookingStateMachine (Phase 3).
 */
enum BookingStatus: string implements HasColor, HasLabel
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

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Confirmed => 'info',
            self::Assigned => 'info',
            self::OnTheWay => 'primary',
            self::InProgress => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'gray',
            self::Rejected => 'danger',
        };
    }
}
