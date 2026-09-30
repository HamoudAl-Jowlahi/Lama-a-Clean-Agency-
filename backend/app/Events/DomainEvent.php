<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * حدث عمل غير مرتبط بتغيير حالة (طلب جديد، رد على شكوى، استحقاق دفعة...).
 * تغييرات الحالة لها StatusChanged. الإشعارات تستمع للاثنين (NotificationRouter).
 *
 * الأسماء: booking.created · contract.created · booking.declined · contract.worker_changed ·
 * contract.worker_released · contract.ending_soon · change_request.submitted · change_request.rejected ·
 * complaint.created · complaint.replied · complaint.customer_message · complaint.status_changed · payment.due
 */
class DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly string $name,
        public readonly Model $subject,
        public readonly array $context = [],
    ) {}

    /** يُطلق بعد نجاح الـ transaction فقط (لا إشعار عن عملية تراجعت). */
    public static function afterCommit(string $name, Model $subject, array $context = []): void
    {
        DB::afterCommit(fn () => event(new self($name, $subject, $context)));
    }
}
