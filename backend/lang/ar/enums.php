<?php

return [
    'user_role' => ['customer' => 'عميل', 'worker' => 'عاملة'],
    'user_status' => ['active' => 'نشط', 'suspended' => 'موقوف'],
    'worker_status' => ['active' => 'نشطة', 'inactive' => 'غير نشطة', 'on_leave' => 'إجازة'],
    'admin_role' => ['super_admin' => 'مدير عام', 'operations' => 'مدير العمليات', 'support' => 'خدمة العملاء'],

    'booking_status' => [
        'pending' => 'قيد المراجعة',
        'confirmed' => 'مؤكد',
        'assigned' => 'تم الإسناد',
        'on_the_way' => 'في الطريق',
        'in_progress' => 'جارٍ التنفيذ',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        'rejected' => 'مرفوض',
    ],
    'assignment_status' => ['pending' => 'بانتظار الرد', 'accepted' => 'مقبول', 'rejected' => 'مرفوض', 'withdrawn' => 'مسحوب'],

    'contract_status' => [
        'pending' => 'قيد المراجعة',
        'confirmed' => 'مؤكد',
        'assigned' => 'تم الإسناد',
        'active' => 'ساري',
        'completed' => 'منتهٍ',
        'terminated' => 'أُنهي مبكراً',
        'cancelled' => 'ملغي',
        'rejected' => 'مرفوض',
    ],
    'assignment_end_reason' => [
        'replaced' => 'استُبدلت',
        'contract_completed' => 'انتهى العقد',
        'terminated' => 'أُنهي العقد',
        'worker_unavailable' => 'غير متاحة',
    ],
    'change_request_type' => ['replace_worker' => 'استبدال العاملة', 'terminate' => 'إنهاء العقد'],
    'change_request_status' => ['open' => 'جديد', 'under_review' => 'قيد المراجعة', 'approved' => 'معتمد', 'rejected' => 'مرفوض'],

    'payment_method' => ['cash' => 'نقداً'],
    'payment_status' => ['due' => 'مستحق', 'collected' => 'تم التحصيل', 'waived' => 'معفى'],

    'complaint_status' => ['open' => 'مفتوحة', 'under_review' => 'قيد المراجعة', 'resolved' => 'تم الحل', 'closed' => 'مغلقة'],
    'complaint_message_kind' => ['message' => 'رسالة', 'status_change' => 'تغيير حالة', 'internal_note' => 'ملاحظة داخلية'],

    'actor_type' => ['customer' => 'العميل', 'worker' => 'العاملة', 'admin' => 'الإدارة', 'system' => 'النظام'],
    'price_unit' => ['fixed' => 'ثابت', 'hour' => 'بالساعة', 'piece' => 'بالقطعة', 'square_meter' => 'بالمتر المربع'],
];
