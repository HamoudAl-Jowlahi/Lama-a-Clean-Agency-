<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * صلاحيات الـ Resource من AdminRole: عرّف ABILITY (مثل 'bookings')
 * فيُستخدم "bookings.view" للعرض و "bookings.manage" للتعديل.
 */
trait GuardedByAbility
{
    public static function canViewAny(): bool
    {
        return Admin::can(static::ABILITY.'.view');
    }

    public static function canView(Model $record): bool
    {
        return Admin::can(static::ABILITY.'.view');
    }

    public static function canCreate(): bool
    {
        return Admin::can(static::ABILITY.'.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Admin::can(static::ABILITY.'.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return false; // لا حذف من اللوحة — السجلات مرتبطة بطلبات وسجلات مالية
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canManage(): bool
    {
        return Admin::can(static::ABILITY.'.manage');
    }
}
