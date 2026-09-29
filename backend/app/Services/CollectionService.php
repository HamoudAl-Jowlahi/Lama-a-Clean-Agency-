<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use App\Models\Payment;
use App\Support\AuditLogger;

/** تسجيل التحصيل النقدي أو الإعفاء (CR-1). الإدارة فقط. */
class CollectionService
{
    public function collect(Payment $payment, AdminUser $admin, ?string $notes = null): Payment
    {
        $this->ensureDue($payment);
        $payment->update([
            'status' => PaymentStatus::Collected,
            'collected_at' => now(),
            'collected_by' => $admin->id,
            'notes' => $notes,
        ]);
        AuditLogger::log($admin, 'payment.collected', $payment, new: ['amount' => $payment->amount]);

        return $payment;
    }

    public function waive(Payment $payment, AdminUser $admin, string $reason): Payment
    {
        $this->ensureDue($payment);
        $payment->update([
            'status' => PaymentStatus::Waived,
            'collected_by' => $admin->id,
            'notes' => $reason,
        ]);
        AuditLogger::log($admin, 'payment.waived', $payment, new: ['amount' => $payment->amount, 'reason' => $reason]);

        return $payment;
    }

    private function ensureDue(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Due) {
            throw BusinessRuleException::make('PAYMENT_NOT_DUE');
        }
    }
}
