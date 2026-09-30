<?php

/*
| نصوص الإشعارات: {الحدث}.{المستلم}.{title|body}
| المستلمون: customer (العميل) · staff (الفريق أو الخادمة) · admins (لوحة الإدارة)
*/

return [
    'view' => 'عرض',
    'booking' => [
        'created' => [
            'admins' => ['title' => 'زيارة جديدة :number', 'body' => 'موعدها :date الساعة :time — بانتظار المراجعة.'],
        ],
        'confirmed' => [
            'customer' => [
                'title' => 'تم تأكيد طلبك',
                'body' => 'زيارتك :number يوم :date الساعة :time مؤكدة. سنبلغك عند إسناد الفريق.',
            ],
        ],
        'rejected' => [
            'customer' => ['title' => 'تعذر قبول طلبك', 'body' => 'نعتذر، لم نتمكن من قبول الطلب :number. :reason'],
        ],
        'assigned' => [
            'customer' => ['title' => 'تم إسناد طلبك', 'body' => ':team سينفذ زيارتك يوم :date الساعة :time.'],
            'staff' => [
                'title' => 'زيارة جديدة لفريقك',
                'body' => 'الطلب :number يوم :date الساعة :time — بانتظار قبول القائد.',
            ],
        ],
        'unassigned' => [
            'staff' => ['title' => 'أُلغي إسناد زيارة', 'body' => 'الطلب :number لم يعد مسنداً لفريقك.'],
        ],
        'declined' => [
            'admins' => ['title' => 'رفض الفريق زيارة', 'body' => ':team رفض الطلب :number — يحتاج إعادة إسناد.'],
        ],
        'on_the_way' => [
            'customer' => ['title' => 'الفريق في الطريق إليك', 'body' => 'فريق التنظيف متجه لتنفيذ طلبك :number.'],
        ],
        'in_progress' => [
            'customer' => ['title' => 'بدأ التنظيف', 'body' => 'بدأ الفريق العمل على طلبك :number.'],
        ],
        'completed' => [
            'customer' => ['title' => 'اكتملت الزيارة', 'body' => 'المبلغ المستحق نقداً :amount. يسعدنا تقييمك للخدمة.'],
        ],
        'cancelled' => [
            'customer' => ['title' => 'تم إلغاء طلبك', 'body' => 'أُلغي الطلب :number. :reason'],
            'staff' => ['title' => 'أُلغيت زيارة', 'body' => 'الطلب :number يوم :date الساعة :time أُلغي.'],
            'admins' => ['title' => 'ألغى العميل طلباً', 'body' => 'الطلب :number يوم :date الساعة :time.'],
        ],
    ],
    'contract' => [
        'created' => [
            'admins' => ['title' => 'طلب عقد جديد :number', 'body' => 'يبدأ :date — بانتظار المراجعة.'],
        ],
        'confirmed' => [
            'customer' => ['title' => 'تم تأكيد عقدك', 'body' => 'العقد :number مؤكد. سنبلغك عند تعيين الخادمة.'],
        ],
        'rejected' => [
            'customer' => ['title' => 'تعذر قبول طلب العقد', 'body' => 'نعتذر، لم نتمكن من قبول العقد :number. :reason'],
        ],
        'assigned' => [
            'customer' => ['title' => 'تم تعيين الخادمة', 'body' => ':worker ستعمل لديك ابتداءً من :date.'],
            'staff' => ['title' => 'عقد جديد', 'body' => 'تم إسنادك للعقد :number ابتداءً من :date.'],
        ],
        'active' => [
            'customer' => ['title' => 'بدأ عقدك', 'body' => 'العقد :number ساري حتى :end_date.'],
            'staff' => ['title' => 'بدأ العقد', 'body' => 'العقد :number ساري اليوم. تجدين التفاصيل في التطبيق.'],
        ],
        'completed' => [
            'customer' => ['title' => 'انتهى عقدك', 'body' => 'انتهى العقد :number. يسعدنا تقييمك للخدمة.'],
            'staff' => ['title' => 'انتهى العقد', 'body' => 'انتهت فترتك في العقد :number.'],
        ],
        'terminated' => [
            'customer' => ['title' => 'تم إنهاء العقد', 'body' => 'أُنهي العقد :number. سيُحتسب المستحق عن الأيام الفعلية.'],
            'staff' => ['title' => 'أُنهي العقد', 'body' => 'أُنهي العقد :number مبكراً.'],
        ],
        'cancelled' => [
            'customer' => ['title' => 'تم إلغاء العقد', 'body' => 'أُلغي العقد :number. :reason'],
            'staff' => ['title' => 'أُلغي العقد', 'body' => 'أُلغي العقد :number قبل بدئه.'],
            'admins' => ['title' => 'ألغى العميل عقداً', 'body' => 'العقد :number كان سيبدأ :date.'],
        ],
        'ending_soon' => [
            'customer' => [
                'title' => 'عقدك ينتهي قريباً',
                'body' => 'ينتهي العقد :number يوم :end_date. يمكنك طلب عقد جديد من التطبيق.',
            ],
            'admins' => ['title' => 'عقد ينتهي قريباً', 'body' => 'العقد :number ينتهي :end_date.'],
        ],
        'worker_changed' => [
            'customer' => ['title' => 'تم تعيين خادمة جديدة', 'body' => ':worker ستعمل لديك ابتداءً من :start_on.'],
            'staff' => ['title' => 'إسناد جديد', 'body' => 'تم إسنادك للعقد :number ابتداءً من :start_on.'],
        ],
        'worker_released' => [
            'staff' => ['title' => 'انتهت فترتك في العقد', 'body' => 'لم تعودي مسندة للعقد :number. شكراً لجهودك.'],
        ],
    ],
    'change_request' => [
        'submitted' => [
            'admins' => ['title' => 'طلب :request_type — :request', 'body' => 'العقد :number · السبب: :reason'],
        ],
        'rejected' => [
            'customer' => ['title' => 'تمت مراجعة طلبك', 'body' => 'لم يُعتمد طلب :request_type (:request). :response'],
        ],
    ],
    'complaint' => [
        'created' => [
            'admins' => ['title' => 'شكوى جديدة :number', 'body' => 'النوع: :type'],
        ],
        'customer_message' => [
            'admins' => ['title' => 'رد جديد من العميل', 'body' => 'على الشكوى :number'],
        ],
        'replied' => [
            'customer' => ['title' => 'رد جديد على شكواك', 'body' => 'ردت خدمة العملاء على الشكوى :number.'],
        ],
        'status_changed' => [
            'customer' => ['title' => 'تحديث على شكواك', 'body' => 'حالة الشكوى :number أصبحت: :status.'],
        ],
    ],
    'payment' => [
        'due' => [
            'customer' => ['title' => 'دفعة مستحقة', 'body' => 'مستحق نقداً :amount عن العقد :number.'],
        ],
    ],
];
