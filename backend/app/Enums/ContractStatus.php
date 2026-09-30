<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * حالات عقد الاستئجار (CR-2). استبدال العاملة لا يغير حالة العقد —
 * يتغير الإسناد فقط في contract_assignments.
 */
enum ContractStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'contract_status';

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Assigned = 'assigned';
    case Active = 'active';
    case Completed = 'completed';
    case Terminated = 'terminated';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Terminated, self::Cancelled, self::Rejected], true);
    }

    /** يمكن للعميل إلغاؤه (قبل البدء فقط). */
    public function isCancellableByCustomer(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Assigned], true);
    }

    /** الحالات التي تحجز العاملة المسندة. */
    public static function occupyingWorker(): array
    {
        return [self::Assigned, self::Active];
    }

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Confirmed => 'info',
            self::Assigned => 'info',
            self::Active => 'primary',
            self::Completed => 'success',
            self::Terminated => 'warning',
            self::Cancelled => 'gray',
            self::Rejected => 'danger',
        };
    }
}
