# لمعة — Phase 6: الإشعارات

> الحالة: **مكتمل ومُتحقق منه** — 11 اختباراً للإشعارات (ضمن 84 اختباراً) على SQLite و MariaDB.
> الدفع الإلكتروني **خارج النطاق** (قرار الدفع نقداً CR-1)؛ جدول `payments` وعمود `method` يسمحان بإضافته لاحقاً.

## ثلاث قنوات

| القناة | لمن | كيف |
|---|---|---|
| **قائمة الإشعارات** داخل التطبيق | العميل والموظفون | تُحفظ دائماً — `GET /api/v1/notifications` |
| **Push** على الجوال | العميل والموظفون | عبر `PushSender` (log / fcm / null) — حسب تفضيلات المستخدم |
| **جرس لوحة الإدارة** | الإدارة حسب الدور | إشعار داخل `/admin` مع زر "عرض" يفتح السجل (تحديث كل 30 ثانية) |

## كيف يعمل

```text
Service (مثلاً BookingService::confirm)
   └─ StateMachine → StatusChanged       ─┐
   └─ DomainEvent (booking.created, ...)  ─┤  بعد نجاح الـ transaction فقط
                                           ▼
                                 NotificationRouter
            ┌──────────────────────────┼──────────────────────────┐
     العميل (AppNotification)   الفريق/الخادمة (AppNotification)   الإدارة (إشعار اللوحة)
        database + Push              database + Push             حسب الصلاحية
```

- **من يستلم ماذا:** مصفوفة في `config/agency.php` (`notifications`)، تعدّلها الإدارة من **الإعدادات ← الإشعارات**.
- **لا يُشعَر الفاعل نفسه:** العميل الذي ألغى لا يستلم "تم إلغاء طلبك"، والإدارة التي أكدت لا تستلم إشعاراً بذلك.
- **اللغة:** النص يُكتب بلغة المستلم (`users.locale`) من `lang/{ar,en}/notifications.php`.
- **الطابور:** إرسال الإشعار يتم في الخلفية (`ShouldQueue`) حتى لا يبطئ الطلب — يلزم تشغيل عامل الطابور في الخادم.

## الأحداث

| الحدث | العميل | الفريق/الخادمة | الإدارة |
|---|:-:|:-:|:-:|
| زيارة جديدة | | | ✓ |
| تأكيد / رفض زيارة | ✓ | | |
| إسناد الزيارة لفريق | ✓ | ✓ كل الأعضاء | |
| رفض القائد للإسناد | | | ✓ |
| سحب الإسناد | | ✓ | |
| في الطريق · بدء التنظيف · اكتمال (مع المبلغ النقدي) | ✓ | | |
| إلغاء زيارة | ✓ | ✓ إن كانت مسندة | ✓ إن ألغاها العميل |
| طلب عقد جديد | | | ✓ |
| تأكيد / رفض / تعيين خادمة / بدء / انتهاء / إنهاء العقد | ✓ | ✓ الخادمة | |
| قرب نهاية العقد (قبل 3 أيام — مرة واحدة) | ✓ | | ✓ |
| طلب استبدال/إنهاء من العميل | | | ✓ |
| اعتماد الاستبدال | ✓ "خادمة جديدة من تاريخ..." | ✓ الجديدة + السابقة | |
| رفض طلب الاستبدال/الإنهاء | ✓ مع رد الإدارة | | |
| شكوى جديدة · رد العميل | | | ✓ خدمة العملاء |
| رد الإدارة · تغيير حالة الشكوى | ✓ (الملاحظة الداخلية لا تُشعِر) | | |
| دفعة عقد مستحقة | ✓ | | |

## Push (FCM)

| `PUSH_DRIVER` | الاستخدام |
|---|---|
| `log` (الافتراضي) | التطوير: يكتب الإشعار في `storage/logs/laravel.log` |
| `fcm` | الإنتاج: Firebase Cloud Messaging (HTTP v1) — بدون مكتبات خارجية |
| `null` | إيقاف مؤقت |

### تفعيل FCM
1. أنشئ مشروعاً في [Firebase Console](https://console.firebase.google.com) وأضف تطبيق Android و iOS.
2. **Project settings ← Service accounts ← Generate new private key** → ملف JSON.
3. ضع الملف **خارج مجلد المشروع** (لا يُرفع لـ Git)، ثم في `.env`:
   ```env
   PUSH_DRIVER=fcm
   FIREBASE_CREDENTIALS=/path/to/service-account.json
   ```
4. شغّل عامل الطابور: `php artisan queue:work` (في الإنتاج عبر Supervisor أو ما يماثله).

- الأجهزة تُسجَّل من التطبيق: `POST /api/v1/device-tokens {token, platform}`.
- الجهاز الذي حُذف منه التطبيق (`UNREGISTERED`) يُحذف رمزه تلقائياً.
- الـ access token من Google يُخزن مؤقتاً قرابة ساعة.

## واجهة التطبيق

**عنصر في القائمة** (`GET /api/v1/notifications`):
```json
{
  "id": "9d2c…", "event": "booking.assigned",
  "title": "تم إسناد طلبك", "body": "فريق أ سينفذ زيارتك يوم 2026-10-05 الساعة 10:00.",
  "subject": { "type": "booking", "id": 12, "number": "BK-2026-000012" },
  "read": false, "created_at": "2026-10-04T09:12:00+00:00"
}
```

**بيانات Push** (لفتح الشاشة عند الضغط): `event`, `subject_type` (booking | contract | complaint), `subject_id`, `subject_number`.

**تفضيلات المستخدم** (شاشة الإعدادات في التطبيق):
```http
PATCH /api/v1/auth/me
{ "notification_preferences": { "orders": true, "complaints": false } }
```
تُوقف الـ Push للفئة فقط؛ الإشعار يبقى في القائمة.

## الملفات

```text
backend/app/Services/NotificationRouter.php        من يستلم ماذا + النصوص + روابط اللوحة
backend/app/Notifications/AppNotification.php      إشعار التطبيق (database + Push)
backend/app/Notifications/Channels/PushChannel.php إرسال + حذف الأجهزة غير الصالحة
backend/app/Contracts/PushSender.php               الواجهة القابلة للاستبدال
backend/app/Push/{Log,Fcm,Null}PushSender.php      المزودون
backend/app/Events/DomainEvent.php                 أحداث العمل غير المرتبطة بتغيير حالة
backend/lang/{ar,en}/notifications.php             النصوص
backend/config/push.php · config/agency.php        الإعدادات والمصفوفة
backend/tests/Feature/Notifications/*              الاختبارات
```
