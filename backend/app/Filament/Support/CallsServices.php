<?php

namespace App\Filament\Support;

use App\Exceptions\BusinessRuleException;
use Filament\Notifications\Notification;

/**
 * لصفحات الإنشاء/التعديل: الحفظ عبر Service؛ خرق قاعدة عمل → إشعار عربي
 * وتبقى الصفحة كما هي (halt) مع التراجع عن أي تغيير جزئي.
 */
trait CallsServices
{
    /**
     * @template T
     *
     * @param  callable(\App\Models\AdminUser): T  $callback
     * @return T
     */
    protected function callService(callable $callback): mixed
    {
        try {
            return $callback(Admin::user());
        } catch (BusinessRuleException $e) {
            Notification::make()->danger()->title($e->userMessage())->send();
            $this->halt(shouldRollbackDatabaseTransaction: true);
        }
    }
}
