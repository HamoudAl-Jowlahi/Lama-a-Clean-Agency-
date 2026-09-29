<?php

/*
|--------------------------------------------------------------------------
| إعدادات لمعة التجارية — القيم الافتراضية
|--------------------------------------------------------------------------
| كل قيمة هنا لم تُحسم في الـ SRS (انظر docs/01 القسم 11). القيمة الفعلية
| تُقرأ عبر App\Support\Settings: جدول settings أولاً (تعدّله الإدارة من
| اللوحة)، ثم هذه القيم. لا تكتب أي قيمة تجارية ثابتة في الكود.
*/

return [

    'brand_name' => 'لمعة',

    // ---- عام ----
    'currency' => env('AGENCY_CURRENCY', 'SAR'),       // مثال — بانتظار قرار السوق
    'timezone' => env('AGENCY_TIMEZONE', 'Asia/Riyadh'),
    'tax_rate' => (float) env('AGENCY_TAX_RATE', 0),   // نسبة مئوية

    // ---- مواعيد الزيارات ----
    'working_hours' => ['start' => '08:00', 'end' => '20:00'],
    'slot_minutes' => 120,
    'min_booking_lead_hours' => 12,

    // ---- سياسات الزيارات ----
    'booking' => [
        'customer_cancel_until_status' => 'assigned', // آخر حالة يمكن للعميل الإلغاء فيها
        'customer_cancel_hours_before' => 6,
        'worker_can_reject_assignment' => true,
        'show_customer_phone_from_status' => 'on_the_way',
    ],

    // ---- العقود (CR-2) ----
    'contracts' => [
        'terms_version' => '2026-10',
        'min_months' => 1,
        'max_months' => 12,
        'payment_schedule' => 'monthly',          // monthly | end_of_contract
        'early_termination_calc' => 'actual_days', // actual_days | full_month
        'replacement_sla_days' => 2,               // مهلة إرسال البديلة
        'max_replacements' => 0,                   // 0 = بلا حد
        'deduct_waiting_days' => true,             // أيام انتظار البديلة لا تُحتسب على العميل
        'ending_reminder_days' => 3,
    ],

    // ---- الدفع (CR-1) ----
    'payments' => [
        'in_app_enabled' => false, // الدفع نقداً عند الإتمام فقط
        'overdue_after_days' => 3,
    ],

    // ---- الشكاوى ----
    'complaint_types' => ['late', 'quality', 'behavior', 'payment', 'other'],
    'change_request_reasons' => ['frequent_delay', 'quality', 'absence', 'behavior', 'no_longer_needed', 'other'],

    // ---- المرفقات ----
    'attachments' => [
        'max_kb' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    ],
];
