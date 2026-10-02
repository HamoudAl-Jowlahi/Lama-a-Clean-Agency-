# لمعة — تطبيق الجوال (Flutter)

تطبيق واحد بثلاث واجهات تُختار حسب الحساب (`GET /auth/me`):

| الحساب | الواجهة |
|---|---|
| عميل | الخدمات · طلب زيارة (الخيار، العنوان، اليوم والوقت من التوفر الفعلي، الملخص — الدفع نقداً) · زياراتي (التتبع، الإلغاء، التقييم) · استئجار عاملة بعقد · عقودي (التقدم، الدفعات، طلب استبدال/إنهاء) · الشكاوى · الإشعارات · العناوين |
| قائد فريق | زيارات الفريق (جديدة/اليوم/القادمة/المنجزة) · قبول/رفض · تحديث الحالة خطوة بخطوة · المبلغ المطلوب تحصيله · الاتصال بالعميل |
| عضو فريق | نفس الزيارات للعرض فقط |
| خادمة | عقودها — بيانات العميل والعنوان خلال فترة عملها فقط |

عربي RTL، خط IBM Plex Sans Arabic مضمّن (يعمل بدون إنترنت)، ألوان Design System.

## التشغيل على جهاز موصول (تطوير)

```bash
php backend/artisan serve --host=127.0.0.1 --port=8000
adb reverse tcp:8000 tcp:8000
cd mobile && flutter run
```

عنوان الـ API الافتراضي `http://127.0.0.1:8000/api/v1`. للإنتاج:

```bash
flutter build apk --release --dart-define=API_BASE=https://api.example.com/api/v1
```

## ملاحظات

- **مسار بأحرف عربية:** Gradle/Java على Windows (ترميز 1252) لا يقرأ المسار `E:\مشاريع\...`. ابنِ من نسخة في مسار إنجليزي (مثلاً `robocopy mobile C:\build\lamaa /MIR /XD build .dart_tool .gradle`).
- **Impeller معطّل** في `AndroidManifest.xml` لأنه يعرض شاشة سوداء على معالجات PowerVR (MediaTek mt6765 — Galaxy A21). يُعاد تقييمه مع تحديثات Flutter.
- الـ HTTP غير المشفر مسموح في نسخة debug فقط (`src/debug/AndroidManifest.xml`).
- إشعارات FCM على الجهاز تحتاج `google-services.json` من Firebase (الخادم جاهز — docs/05).

## الملفات

```text
lib/core/        api.dart (Dio + أخطاء موحدة + Idempotency-Key) · session.dart · theme.dart · widgets.dart
lib/features/    auth · customer · worker · shared (الإشعارات، الحساب)
```
