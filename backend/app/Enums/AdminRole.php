<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * أدوار الإدارة وصلاحياتها — مكان واحد تقرأ منه كل صفحات اللوحة.
 *
 * super_admin: كل شيء · operations: التشغيل اليومي (بلا إعدادات ولا مستخدمي الإدارة)
 * support: الشكاوى والتقييمات، ورؤية الطلبات والعقود والعملاء دون تعديلها.
 */
enum AdminRole: string implements HasColor, HasLabel
{
    use EnumHelpers;

    public const LANG_KEY = 'admin_role';

    case SuperAdmin = 'super_admin';
    case Operations = 'operations';
    case Support = 'support';

    /** @var array<string, list<string>> */
    private const ABILITIES = [
        'operations' => [
            'bookings.view', 'bookings.manage',
            'contracts.view', 'contracts.manage',
            'payments.view', 'payments.manage',
            'complaints.view', 'complaints.manage',
            'customers.view', 'customers.manage',
            'staff.view', 'staff.manage',
            'catalog.view', 'catalog.manage',
            'ratings.view', 'ratings.manage',
            'audit.view',
        ],
        'support' => [
            'bookings.view',
            'contracts.view',
            'payments.view',
            'complaints.view', 'complaints.manage',
            'customers.view',
            'staff.view',
            'catalog.view',
            'ratings.view', 'ratings.manage',
        ],
    ];

    public function can(string $ability): bool
    {
        return $this === self::SuperAdmin || in_array($ability, self::ABILITIES[$this->value] ?? [], true);
    }

    /** لون الشارة في لوحة الإدارة (Filament). */
    public function getColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Operations => 'primary',
            self::Support => 'info',
        };
    }
}
