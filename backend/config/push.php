<?php

/*
|--------------------------------------------------------------------------
| إشعارات الجوال (Push) — قابلة للاستبدال
|--------------------------------------------------------------------------
| log  : يكتب الإشعار في سجل Laravel (للتطوير — لا يحتاج أي حساب)
| fcm  : Firebase Cloud Messaging (HTTP v1). يحتاج ملف Service Account
|        من Firebase Console خارج Git، ومساره في FIREBASE_CREDENTIALS.
| null : لا يرسل شيئاً
*/

return [
    'driver' => env('PUSH_DRIVER', 'log'),

    'fcm' => [
        'credentials' => env('FIREBASE_CREDENTIALS'), // مسار ملف JSON — لا تضعه داخل المشروع
        'project_id' => env('FIREBASE_PROJECT_ID'),   // اختياري: يُقرأ من الملف إن لم يُحدد
        'timeout' => 10,
    ],
];
