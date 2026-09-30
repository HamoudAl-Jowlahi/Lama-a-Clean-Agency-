<?php

namespace App\Observers;

use App\Models\AdminUser;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * يسجل في Audit Log كل تغيير على الخدمات والأسعار والباقات عندما يجريه مستخدم إدارة
 * (يشمل أسعار الـ Repeater التي تُحفظ مباشرة). التغييرات من الـ Seeders/Console لا تُسجل.
 */
class CatalogAuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())->except(['updated_at'])->all();
        if ($changes === []) {
            return;
        }

        $this->log($model, 'updated', array_intersect_key($model->getOriginal(), $changes), $changes);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getOriginal(), []);
    }

    private function log(Model $model, string $event, array $old, array $new): void
    {
        $admin = auth('admin')->user();
        if (! $admin instanceof AdminUser) {
            return;
        }

        AuditLogger::log($admin, $model->getMorphClass().'.'.$event, $model, $old, $new);
    }
}
