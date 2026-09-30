<?php

namespace App\Contracts;

/**
 * مرسل إشعارات الجوال — يُختار من config/push.php (log | fcm | null).
 * استبدال المزود = كلاس جديد يطبق هذه الواجهة، بلا تغيير في بقية النظام.
 */
interface PushSender
{
    /**
     * @param  list<string>  $tokens  رموز أجهزة المستخدم (device_tokens)
     * @param  array<string, string>  $data  بيانات للتطبيق (نوع الحدث، رقم الطلب...) — قيم نصية فقط
     * @return list<string> الرموز غير الصالحة (الجهاز حذف التطبيق مثلاً) لتُحذف من قاعدة البيانات
     */
    public function send(array $tokens, string $title, string $body, array $data = []): array;
}
