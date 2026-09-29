<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** يسجل كل عملية إدارية حساسة. تستدعيه الـ Services فقط. */
class AuditLogger
{
    public static function log(?AdminUser $admin, string $action, ?Model $subject = null, array $old = [], array $new = []): AuditLog
    {
        $request = app()->runningInConsole() ? null : request();

        return AuditLog::create([
            'admin_user_id' => $admin?->id,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
