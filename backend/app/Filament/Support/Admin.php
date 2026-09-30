<?php

namespace App\Filament\Support;

use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * أدوات مشتركة لصفحات لوحة الإدارة:
 * - المستخدم الحالي وصلاحياته (AdminRole::can)
 * - تنفيذ إجراء عبر الـ Services: خرق قاعدة عمل → إشعار أحمر بالرسالة العربية،
 *   ويبقى النموذج مفتوحاً للتصحيح. لا منطق أعمال في اللوحة نفسها.
 */
final class Admin
{
    public static function user(): AdminUser
    {
        /** @var AdminUser */
        return auth('admin')->user();
    }

    public static function can(string $ability): bool
    {
        $user = auth('admin')->user();

        return $user instanceof AdminUser && $user->hasAbility($ability);
    }

    /**
     * @template T
     *
     * @param  callable(AdminUser): T  $callback
     * @return T|null
     */
    public static function run(Action $action, callable $callback, ?string $success = null): mixed
    {
        try {
            $result = $callback(self::user());
        } catch (BusinessRuleException $e) {
            Notification::make()->danger()->title($e->userMessage())->send();
            $action->halt();

            return null;
        }

        if ($success) {
            Notification::make()->success()->title($success)->send();
        }

        return $result;
    }
}
