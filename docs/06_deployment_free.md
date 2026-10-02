# لمعة — ما عليك أنت، والنشر على الإنترنت مجاناً

> آخر تحديث: أكتوبر 2026. شروط الخدمات المجانية تتغير — راجع صفحة كل خدمة قبل التسجيل (الروابط في آخر الملف).

**الخلاصة:** الكود جاهز للنشر. الجزء الباقي يحتاج **حساباتك أنت** (لأنها باسمك وبياناتك ودفعك)، ثم أكمل أنا الباقي بالمعلومات التي ترسلها.

---

## 1. ما عليك أنت — ولماذا

### أ. ضروري قبل الإطلاق

| # | المهمة | لماذا أنت وليس أنا | التكلفة | ماذا ترسل لي بعدها |
|---|---|---|---|---|
| 1 | **حساب Firebase** وإنشاء مشروع "لمعة" | إشعارات الجوال (Push) تمر عبر خوادم Google. المشروع يكون ملكك أنت ويرتبط بحسابك في Google | مجاني | ملف `google-services.json` (أضعه في التطبيق) — **لا ترسل** مفتاح Service Account، بل تضعه أنت في Render (الخطوة 5-هـ) |
| 2 | **حسابات الاستضافة**: Render · Aiven · Cloudflare · cron-job.org | تسجيل باسمك وبريدك، وبعضها يطلب بطاقة للتحقق فقط | مجاني (التفاصيل في القسم 3) | رابط السيرفر بعد النشر، مثل `https://lamaa-api.onrender.com` |
| 3 | **بريد وكلمة مرور مدير اللوحة** | أول حساب مدير لـ `/admin`. كلمة المرور سرية ولا تُكتب في الكود | — | لا شيء — تضعها في Render فقط |
| 4 | **بيانات الوكالة الحقيقية** | الموجود الآن بيانات تجريبية: الخدمات، الأسعار، باقات العقود، نص شروط العقد، نسبة الضريبة، رقم التواصل | — | القائمة النهائية (أو تدخلها بنفسك من اللوحة) |
| 5 | **حسابات الموظفين والفرق** | أسماء وأرقام حقيقية لأشخاص حقيقيين | — | تُدخل من لوحة الإدارة ← الموظفون / الفرق |
| 6 | **سياسة الخصوصية** (صفحة على الإنترنت) | Google Play يرفض أي تطبيق يجمع بيانات بدونها، وهي إقرار قانوني باسم الوكالة | مجاني | موافقتك على المسودة — أكتبها لك وننشرها |

### ب. للنشر في المتاجر

| # | المهمة | لماذا | التكلفة |
|---|---|---|---|
| 7 | **حساب Google Play Console** | لنشر التطبيق في Play. يطلب هوية وتحققاً باسمك | **25$ مرة واحدة** |
| 8 | **12 مختبِراً لمدة 14 يوماً** | سياسة Google: الحسابات **الشخصية** الجديدة لا تنشر للعامة قبل اختبار مغلق مع 12 شخصاً مستمرين 14 يوماً. **حساب منظمة** (يحتاج رقم D-U-N-S للشركة) معفى | مجاني — يحتاج أشخاصاً |
| 9 | **مفتاح توقيع التطبيق** (keystore) | كل تحديث للتطبيق يجب أن يُوقّع بنفس المفتاح. أُنشئه لك، و**أنت تحفظ نسخته وكلمة مروره** في مكان آمن (ليس في GitHub) | مجاني |
| 10 | **iPhone** (اختياري) | يتطلب جهاز Mac + حساب Apple Developer | **99$ سنوياً** |

### ج. اختياري لاحقاً

| المهمة | الفائدة | التكلفة |
|---|---|---|
| **نطاق خاص** (مثل `lamaa.sa`) | رابط احترافي وثابت، ولا يتغير التطبيق إذا نقلنا الاستضافة | ~10–15$ سنوياً |
| **رسائل SMS للتحقق من الجوال (OTP)** | حالياً التسجيل لا يتحقق من ملكية رقم الجوال | مدفوع حسب المزود |
| **استضافة مدفوعة** عند بدء العمل الفعلي | انظر القسم 6 — الخطة المجانية للتجربة | ~5–7$ شهرياً |

---

## 2. الشكل النهائي على الإنترنت (مجاناً)

```text
تطبيق الجوال ──HTTPS──▶  Render (خدمة واحدة: Laravel API + لوحة الإدارة /admin)
                              │
                              ├──▶ Aiven MySQL        قاعدة البيانات (1 GB)
                              ├──▶ Cloudflare R2      صور الشكاوى وطلبات الاستبدال (10 GB)
                              └──▶ Firebase FCM       إشعارات الجوال

cron-job.org ──▶ /up                        كل 10 دقائق: يبقي السيرفر مستيقظاً
cron-job.org ──▶ /api/v1/internal/cron/daily يومياً 00:10: تفعيل العقود وإنشاء الاستحقاقات
```

**لماذا خدمة واحدة للـ API واللوحة؟** لوحة الإدارة (Filament) جزء من نفس مشروع Laravel وتستخدم نفس قاعدة البيانات. فصلها يضاعف التكلفة والتعقيد بلا فائدة.

---

## 3. الخدمات التي سألت عنها — ماذا يناسب ولماذا

| الخدمة | المجاني فيها | يناسب لمعة؟ | السبب |
|---|---|---|---|
| **Render** | خدمة ويب Docker، 750 ساعة شهرياً، 512MB | ✅ **السيرفر (API + اللوحة)** | يشغّل Laravel كما هو. ينام بعد 15 دقيقة بلا زيارات ويحتاج نحو دقيقة ليصحو — نعالجها بالمنبّه (cron-job.org). القرص يُمسح عند كل إعادة تشغيل، لذلك الصور على R2 |
| **Aiven** | MySQL: 1GB، بلا مدة انتهاء، بلا بطاقة | ✅ **قاعدة البيانات (الأفضل)** | MySQL هو ما اختبرنا عليه المشروع (87 اختباراً على MariaDB). قد يوقف الخدمة إذا بقيت مهملة طويلاً (مع إشعار مسبق) |
| **Neon** | PostgreSQL: 0.5GB، 100 ساعة حوسبة شهرياً، ينام بعد 5 دقائق | ⚠️ **بديل** | يعمل مع Laravel (أضفت دعم PostgreSQL في الحاوية)، لكن المشروع **لم يُختبر بعد على PostgreSQL**، والمساحة نصف Aiven |
| **Cloudflare R2** | 10GB تخزين بلا رسوم تنزيل | ✅ **الصور والمرفقات** | يطلب بطاقة أو PayPal **للتحقق فقط** عند التفعيل، ولا يخصم ضمن الحد المجاني |
| **cron-job.org** | مهام مجدولة مجانية | ✅ **المنبّه والمهمة اليومية** | Render المجاني لا يشغّل مهام مجدولة |
| **Vercel** | Hobby مجاني | ❌ | خطة Hobby **للاستخدام الشخصي غير التجاري** ولمعة مشروع تجاري. والقرص مؤقت ولا يوجد عامل طوابير |
| **InfinityFree** | PHP + MySQL | ❌ **لا يصلح للتطبيق** | عنده نظام حماية يشترط متصفحاً (JavaScript + Cookies)، فـ**يحجب طلبات تطبيق الجوال** للـ API. لا يوجد SSH/Composer، وقاعدة بياناته لا تُفتح من خارجه |
| **Railway** | 5$ تجربة لمدة 30 يوماً، ثم 1$ شهرياً | ❌ للتشغيل الدائم | 1$ شهرياً لا يكفي لتشغيل السيرفر طوال الشهر |
| **Koyeb** | خدمة واحدة 512MB، تنام بعد ساعة | ⚠️ بديل لـ Render | يطلب بطاقة منذ فبراير 2026. الـ Dockerfile نفسه يعمل عليه |

---

## 4. ما جهّزته في المشروع للنشر

| الملف | ماذا يفعل |
|---|---|
| [`render.yaml`](../render.yaml) | Blueprint: ينشئ الخدمة على Render بكل المتغيرات، وتملأ أنت الأسرار فقط |
| [`backend/Dockerfile`](../backend/Dockerfile) | PHP 8.3 + Apache + امتدادات MySQL/PostgreSQL/intl |
| [`backend/docker/start.sh`](../backend/docker/start.sh) | عند التشغيل: المنفذ، الكاش، الترحيلات، والبيانات الأولية في أول مرة |
| `POST /api/v1/internal/cron/daily` | المهمة اليومية عبر رابط محمي برمز سري (`CRON_TOKEN`)، ومعطّل إذا لم يُضبط الرمز |
| `ATTACHMENTS_DISK=s3` | المرفقات على R2 بدل قرص السيرفر المؤقت |
| `FIREBASE_CREDENTIALS_BASE64` · `DB_SSL_CA_BASE64` | الملفات السرية كمتغيرات بيئة، لأن الاستضافة لا تسمح برفع ملفات |

> ⚠️ **صراحة:** الـ Dockerfile لم يُبنَ على هذا الجهاز (Docker Desktop غير مشغّل). أول نشر على Render هو أول تجربة فعلية له. إذا فشل البناء أرسل لي سجل البناء (Logs) وأصلحه.

---

## 5. خطوات النشر

### أ. قاعدة البيانات — Aiven MySQL
1. سجّل في [aiven.io](https://aiven.io) ← **Create service** ← **MySQL** ← الخطة **Free**.
2. من صفحة الخدمة انسخ: `Host` · `Port` · `User` · `Password` · `Database` (الافتراضية `defaultdb`).
3. نزّل **CA certificate** (ملف `ca.pem`)، ثم حوّله إلى Base64 في PowerShell:
   ```powershell
   [Convert]::ToBase64String([IO.File]::ReadAllBytes("C:\path\ca.pem")) | Set-Clipboard
   ```
   القيمة الآن في الحافظة وستلصقها في `DB_SSL_CA_BASE64`.

> **بديل Neon:** `DB_CONNECTION=pgsql` و `DB_SSLMODE=require`، وبيانات الاتصال من لوحة Neon. لا يلزم `DB_SSL_CA_BASE64`.

### ب. الصور — Cloudflare R2
1. [dash.cloudflare.com](https://dash.cloudflare.com) ← **R2** ← فعّله (يطلب وسيلة دفع للتحقق) ← **Create bucket** باسم `lamaa-files`.
2. **Manage API Tokens** ← **Create API token** بصلاحية *Object Read & Write* على هذا الـ bucket فقط.
3. احفظ: `Access Key ID` · `Secret Access Key` · ورابط الـ endpoint `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`.

### ج. مفتاح التطبيق
على جهازك، في مجلد `backend`:
```bash
php artisan key:generate --show
```
انسخ الناتج (يبدأ بـ `base64:`).

### د. السيرفر — Render
1. سجّل في [render.com](https://render.com) بحساب GitHub.
2. **New** ← **Blueprint** ← اختر المستودع `Lama-a-Clean-Agency-`. سيقرأ `render.yaml` تلقائياً.
3. املأ المتغيرات المطلوبة:

| المتغير | القيمة |
|---|---|
| `APP_KEY` | من الخطوة ج |
| `APP_URL` | `https://lamaa-api.onrender.com` (الاسم الذي يعطيك إياه Render) |
| `DB_HOST` · `DB_PORT` · `DB_DATABASE` · `DB_USERNAME` · `DB_PASSWORD` | من Aiven |
| `DB_SSL_CA_BASE64` | من الخطوة أ-3 |
| `AWS_ACCESS_KEY_ID` · `AWS_SECRET_ACCESS_KEY` · `AWS_BUCKET` · `AWS_ENDPOINT` | من R2 |
| `SEED_ADMIN_EMAIL` · `SEED_ADMIN_PASSWORD` | بريد وكلمة مرور قوية لمدير اللوحة |

4. **Apply** وانتظر البناء (أول مرة 5–10 دقائق).
5. افتح `https://<رابطك>/admin` وسجّل الدخول، ثم تأكد من `https://<رابطك>/api/v1/services`.
6. **مهم:** بعد أول تشغيل ناجح غيّر `SEED_ON_START` إلى `false`.
7. انسخ قيمة `CRON_TOKEN` (يولّدها Render تلقائياً) للخطوة التالية.

### هـ. المنبّه والمهمة اليومية — cron-job.org
أنشئ حساباً في [cron-job.org](https://cron-job.org) ثم مهمتين:

| المهمة | الرابط | التوقيت | إعدادات |
|---|---|---|---|
| إبقاء السيرفر مستيقظاً | `https://<رابطك>/up` | كل 10 دقائق | GET |
| مهمة العقود اليومية | `https://<رابطك>/api/v1/internal/cron/daily` | يومياً 00:10 بتوقيت `Asia/Riyadh` | **POST** + Header: `X-Cron-Token: <CRON_TOKEN>` |

> 750 ساعة مجانية تكفي خدمة واحدة تعمل طوال الشهر (31 يوماً = 744 ساعة).

### و. الإشعارات — Firebase
1. [console.firebase.google.com](https://console.firebase.google.com) ← **Add project** باسم "Lamaa".
2. **Add app** ← Android ← Package name: `com.lamaa.lamaa` ← نزّل `google-services.json` **وأرسله لي** لأربطه بالتطبيق.
3. **Project settings** ← **Service accounts** ← **Generate new private key**. هذا الملف سري: لا ترسله لأحد.
4. حوّله إلى Base64 (نفس أمر PowerShell في الخطوة أ-3) وضعه في Render: `FIREBASE_CREDENTIALS_BASE64`، ثم غيّر `PUSH_DRIVER` إلى `fcm`.

### ز. التطبيق على السيرفر الحقيقي
عندما ترسل لي الرابط، أبني نسخة release تتصل به:
```bash
flutter build apk --release --dart-define=API_BASE=https://<رابطك>/api/v1
```
وللنشر في Play: `flutter build appbundle` مع مفتاح التوقيع (البند 9).

---

## 6. حدود الخطة المجانية — بصراحة

- **أول طلب بعد نوم السيرفر يتأخر نحو دقيقة.** المنبّه كل 10 دقائق يقلل ذلك، لكنه ليس ضماناً.
- **لا يوجد عامل طوابير:** الإشعارات تُرسل أثناء الطلب نفسه (`QUEUE_CONNECTION=sync`)، فبعض الطلبات أبطأ قليلاً.
- **السعة:** قاعدة 1GB وصور 10GB تكفي للتجربة وبداية العمل، وليس لسنوات.
- **لا اتفاقية مستوى خدمة:** إذا توقفت خدمة مجانية فلا تعويض.
- **التوصية:** المجاني للتجربة وعرض المشروع. عند أول عملاء حقيقيين انتقل إلى خطة مدفوعة صغيرة: Render Starter، أو خادم VPS بنحو 5$ شهرياً يشغّل كل شيء مع cron وعامل طوابير. الـ Dockerfile نفسه يعمل هناك.

## 7. أمان — لا تتنازل عنه

- لا تضع `.env` أو المفاتيح أو `ca.pem` أو ملف Firebase الخاص في GitHub. ملف `.gitignore` يمنع `.env`، والباقي مسؤوليتك.
- اترك `APP_DEBUG=false` في الإنتاج.
- كلمة مرور المدير قوية، وغيّرها من اللوحة بعد أول دخول.
- `CRON_TOKEN` سري. إذا انكشف أعد توليده من Render وحدّث cron-job.org.

---

## المصادر (أكتوبر 2026)
- Render Free: https://render.com/docs/free — وتغييرات خطط Render في أغسطس 2026: https://jwatte.com/blog/render-com-platform-review.md
- Neon Pricing: https://neon.com/pricing
- Aiven MySQL Free: https://aiven.io/docs/products/mysql/concepts/mysql-free-tier
- Cloudflare R2 (الحد المجاني وطلب وسيلة الدفع): https://costbench.com/software/cloud-infrastructure/cloudflare-r2/free-plan/
- InfinityFree ونظام الحماية مع التطبيقات: https://forum.infinityfree.com/t/working-on-an-application-with-an-api/88725/2
- Vercel (Laravel وحدود Cron في Hobby): https://vercel.com/kb/guide/laravel-php-with-docker · https://vercel.com/docs/cron-jobs/usage-and-pricing
- Railway Free Trial: https://docs.railway.com/pricing/free-trial
- Koyeb Free Instance: https://www.koyeb.com/docs/faq/pricing
- Google Play (12 مختبِراً / 14 يوماً): https://www.testerscommunity.com/guides/how-many-testers-do-you-need-google-play
