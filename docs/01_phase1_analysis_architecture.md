# لمعة (Lam'a) — Phase 1: تحليل المتطلبات والمخطط المعماري

> المرجع: `cleaning_agency_SRS.md` + `cleaning_agency_AI_agent_prompt.md` + `cleaning_agency_UI_UX_design_prompt.md`
> اسم المنتج المعتمد حالياً: **لمعة** (Lam'a).
> الحالة: **مسودة للمراجعة** — لا يبدأ Phase 2 قبل اعتماد القرارات في القسم 11.

### سجل تغييرات النطاق (بموافقة صاحب المشروع)
| # | التغيير | الأثر |
|---|---|---|
| CR-1 | **لا دفع داخل التطبيق.** الدفع نقداً عند إتمام الخدمة. | حذف بوابة الدفع وشاشة الدفع والـ Webhooks. جدول `payments` يبقى لتسجيل **التحصيل النقدي** فقط. |
| CR-2 | **استئجار عاملة بعقد** (مثلاً دوام كامل لمدة شهر) بجانب الزيارة الواحدة، مع إمكانية **طلب استبدال العاملة أو إنهاء العقد** من التطبيق عند وجود مشكلة. | نوع طلب جديد (عقد)، جداول جديدة، شاشات جديدة للعميل والعاملة والإدارة. |
| CR-3 | **الزيارات تنفذها فرق ثابتة** (الفريق يروح ينظف ويرجع)، و**الخادمات للعقود فقط** — لا "في الطريق" للخادمة. **قائد الفريق وحده** يقبل الزيارة ويحدّث حالتها ويستلم المبلغ. **القبول خطوة صريحة** قبل "في الطريق". | نوعا موظفين (`cleaner` / `housekeeper`)، جداول `teams` و `team_members`، الإسناد لفريق (`booking_assignments`)، تقييم الزيارة للفريق. التفاصيل في القسم 4ب. |

---

## 1. ملخص النظام

النظام يقدم **نوعين من الخدمة**:

| | زيارة (Visit) | عقد استئجار (Contract) |
|---|---|---|
| الفكرة | **فريق** يأتي مرة واحدة لتنظيف مكان معين ثم يعود (CR-3) | **خادمة** تعمل لدى العميل لفترة (شهر أو أكثر) حسب باقة |
| المدة | ساعات | شهر / عدة أشهر |
| التسعير | حسب الخدمة (`service_prices`) | سعر شهري حسب الباقة (`contract_plans`) |
| الدفع | نقداً عند إتمام الزيارة | نقداً عند نهاية كل شهر من العقد (افتراضي — انظر 11) |
| تغيير العاملة | لا ينطبق | طلب استبدال من التطبيق ← الإدارة تعتمد وتسند عاملة بديلة |
| الإنهاء | إلغاء قبل التنفيذ | طلب إنهاء مبكر من التطبيق ← الإدارة تعتمد |

ثلاث واجهات فوق Backend واحد:

| المكوّن | المستخدم | التقنية المقترحة | طريقة المصادقة |
|---|---|---|---|
| تطبيق الجوال | العميل + العاملة (صلاحيات منفصلة داخل تطبيق واحد) | Flutter | Bearer Token (Laravel Sanctum) |
| لوحة الإدارة | الإدارة | Laravel + Filament (يدعم RTL) | Session + Guard مستقل `admin` |
| Backend / API | — | Laravel 12 (PHP 8.2 ✓ متوفر) | — |
| قاعدة البيانات | — | MySQL 8 (عبر Docker ✓ متوفر) | — |

**سبب اختيار Filament:** الـ SRS يفضّل Laravel-based Dashboard، وFilament يعطي جداول وفلاتر ونماذج وResponsive وRTL جاهزة، ويبقى منطق الأعمال في Services مشتركة مع الـ API.

---

## 2. الأدوار والصلاحيات

| المورد / العملية | Customer | Worker | Admin |
|---|---|---|---|
| تصفح الخدمات والباقات | ✓ | — | ✓ (إدارة) |
| عناوينه | ✓ ملكه فقط | — | عرض |
| إنشاء طلب زيارة / طلب عقد | ✓ | — | (اختياري لاحقاً) |
| عرض الطلب / العقد | ما يخصه فقط | المسند إليها فقط | الكل |
| إلغاء زيارة / عقد لم يبدأ | وفق السياسة (إعداد) | — | ✓ |
| تأكيد / رفض الطلب أو العقد | — | — | ✓ |
| إسناد عاملة | — | — | ✓ |
| قبول / رفض إسناد الزيارة | — | **قائد الفريق فقط** وفق السياسة (إعداد) | — |
| تحديث حالة تنفيذ الزيارة | — | **قائد الفريق فقط**، بعد القبول الصريح | ✓ |
| رؤية تفاصيل الزيارة | — | كل أعضاء الفريق المسند (الهاتف والمبلغ للقائد فقط) | ✓ |
| رؤية العقد | — | الخادمة المسندة فقط، خلال فترتها | ✓ |
| **طلب استبدال العاملة / إنهاء العقد** | ✓ لعقوده النشطة | — | ✓ معالجة واعتماد |
| **تسجيل التحصيل النقدي** | — | (انظر 11) | ✓ |
| التقييم | ✓ بعد اكتمال الزيارة / نهاية العقد | عرض تقييماتها | إدارة / إخفاء |
| الشكاوى | إنشاء ومتابعة (مرتبطة بزيارة أو عقد) | — | معالجة والرد |
| بيانات هاتف العميل | — | زيارة: أثناء التنفيذ فقط · عقد: طوال فترة العقد النشط | ✓ |

**التطبيق في الخادم:** `Policies` لكل Model، Middleware `ability:customer|worker`، Guard منفصل للإدارة بأدوار (`super_admin`, `operations`, `support`)، والـ Scoping في الاستعلام نفسه (`Contract::forCustomer($user)`) لمنع تسريب القوائم.

---

## 3. المعمارية

```
┌──────────────┐      ┌───────────────────────────────────────────────┐
│ Flutter App  │─────▶│  Laravel                                       │
│ Customer/    │ REST │  ┌────────────┐  ┌──────────────────────────┐  │
│ Worker       │ /v1  │  │ API Layer  │─▶│ Domain Services          │  │
└──────────────┘      │  │ Controllers│  │ BookingService           │  │
                      │  │ FormRequest│  │ ContractService          │  │
┌──────────────┐      │  │ Resources  │  │ ContractChangeService    │  │
│ Filament     │─────▶│  └────────────┘  │ AssignmentService        │  │
│ Admin (web)  │      │                  │ PricingService           │  │
└──────────────┘      │                  │ CollectionService (نقدي) │  │
                      │                  │ ComplaintService         │  │
                      │                  │ State Machines           │  │
                      │                  └──────────┬───────────────┘  │
                      │          ┌──────────────────┼──────────────┐   │
                      │          ▼                  ▼              ▼   │
                      │   NotificationSender   Scheduler (Jobs)  AuditLogger
                      │   ├ Database           تفعيل/إنهاء العقود       │
                      │   └ FCM                واستحقاقات الدفع الشهرية  │
                      └───────────────────────────────────────────────┘
                                          │
                                      MySQL 8
```

### قواعد التصميم
1. **منطق الأعمال في Services فقط** — الـ Controllers وصفحات Filament تستدعي نفس الـ Service.
2. **حالات مركزية:** `BookingStatus`, `ContractStatus`, `ChangeRequestStatus`, `ComplaintStatus` كـ Enums، ولكل من الزيارة والعقد State Machine تحدد الانتقالات ومن يملكها. Flutter يستخدم نفس القيم النصية.
3. **الدفع (CR-1):** لا بوابة دفع. `payments` = سجل استحقاق وتحصيل نقدي (`due → collected / waived`). عمود `method` يبقى (`cash` حالياً) حتى يمكن إضافة الدفع الإلكتروني مستقبلاً دون إعادة هيكلة.
4. **الإشعارات قابلة للاستبدال:** Events ← Listeners ← Channels (Database + FCM).
5. **المهام المجدولة (Laravel Scheduler):** تحويل العقد إلى `active` عند تاريخ البدء، إلى `completed` عند تاريخ الانتهاء، إنشاء استحقاق الدفع الشهري، وتذكير قبل نهاية العقد.
6. **توفر العاملة مصدر واحد:** `AvailabilityService` يمنع إسناد عاملة لديها عقد نشط أو زيارة متعارضة — يُستخدم في إسناد الزيارات والعقود والاستبدال.
7. **الإعدادات بدل الافتراضات:** كل قيمة تجارية غير محسومة في `config/agency.php` + جدول `settings` قابل للتعديل من اللوحة.
8. **Audit Log** لكل عملية إدارية حساسة.
9. **توحيد الاستجابة والأخطاء** (القسم 8).

### هيكل Laravel المقترح
```
backend/app/
  Enums/          BookingStatus, ContractStatus, ChangeRequestType, ChangeRequestStatus,
                  AssignmentStatus, ComplaintStatus, PaymentStatus, UserRole
  Models/  Policies/  Events/  Listeners/  Notifications/  Jobs/
  Services/       Booking/, Contract/, Assignment/, Availability/, Pricing/,
                  Collection/, Complaint/, Rating/
  StateMachines/  BookingStateMachine.php, ContractStateMachine.php
  Http/Controllers/Api/V1/{Customer,Worker,Common}/  Requests/  Resources/
  Filament/       Resources/, Pages/, Widgets/
  Support/        AuditLogger, ApiResponse
```

### هيكل Flutter المقترح
```
mobile/lib/
  core/      api (dio), secure storage, router (go_router + role guards), theme, l10n
  shared/    AppButton, AppCard, StatusBadge, EmptyState, ErrorState ...
  features/  auth/ services/ booking/ contracts/ addresses/ ratings/
             complaints/ notifications/ profile/ worker/
```
Riverpod · freezed/json_serializable · flutter_localizations (العربية افتراضية، RTL).

---

## 4. دورة حياة الزيارة (Booking)

```
            ┌──────────► rejected (Admin)
pending ──► confirmed ──► assigned ──► on_the_way ──► in_progress ──► completed ──► (تحصيل نقدي)
  │ (Admin)    │ (Admin      │ ▲ (Worker)       (Worker)       (Worker/Admin)
  │            │  assigns)   │ └── رفض العاملة للإسناد → يعود إلى confirmed
  ▼            ▼             ▼
cancelled ◄────┴─────────────┘   (العميل وفق السياسة / الإدارة قبل completed)
```

| من | إلى | الفاعل | شرط |
|---|---|---|---|
| pending | confirmed / rejected | Admin | الرفض بسبب إلزامي |
| confirmed | assigned | Admin | عاملة متاحة (AvailabilityService) |
| assigned | confirmed | Worker (رفض) / Admin (سحب) | حسب السياسة، سبب إلزامي |
| assigned → on_the_way → in_progress | | Worker | |
| in_progress | completed | Worker / Admin | يُنشأ استحقاق دفع نقدي `due` |
| غير نهائية | cancelled | Customer (حسب السياسة) / Admin | سبب + Audit |

---

## 4ب. فرق الزيارات والخادمات — CR-3

| | فريق الزيارات (`cleaner`) | الخادمة (`housekeeper`) |
|---|---|---|
| يعمل في | الزيارات فقط | العقود فقط |
| الإسناد | الزيارة تُسند **لفريق كامل** | العقد يُسند **لخادمة** |
| الحالات في التطبيق | قبول ← في الطريق ← جارٍ التنفيذ ← مكتمل | لا حالات تنفيذ — تعرض بيانات العقد وفترتها |
| من يحدّث | **قائد الفريق فقط** (ويستلم المبلغ نقداً) | — (التفعيل والانتهاء تلقائي) |
| التقييم | للفريق | للخادمة |

- الفريق ثابت تُنشئه الإدارة: اسم + أعضاء + قائد من الأعضاء. العضو في فريق واحد فقط (`TeamService` يفرض ذلك).
- **الطاقة الاستيعابية** للزيارات في أي فترة = عدد الفرق المفعّلة التي لها قائد نشط.
- الفريق لا يُسند لزيارتين متداخلتين في الوقت (`TEAM_BUSY`).
- عضو الفريق غير القائد يرى تفاصيل الزيارة (العنوان والخدمة والملاحظات) لكن بدون هاتف العميل أو المبلغ، ولا ينفذ أي إجراء (`TEAM_LEADER_ONLY`).
- `GET /auth/me` يرجع `worker.type` و `worker.team.is_leader` — التطبيق يعرض الواجهة المناسبة.

---

## 5. دورة حياة العقد (Contract) — CR-2

```
pending ──► confirmed ──► assigned ──► active ──────────────► completed
 │ (Admin)   │ (Admin      │ (Scheduler عند       │ (Scheduler عند تاريخ الانتهاء)
 │           │  assigns)   │  تاريخ البدء)         │
 ▼           ▼             ▼                      ▼
rejected   cancelled ◄─────┘                  terminated
(Admin)    (العميل أو الإدارة قبل البدء)      (إنهاء مبكر: طلب من العميل تعتمده الإدارة، أو قرار إداري)
```

| من | إلى | الفاعل | شرط |
|---|---|---|---|
| pending | confirmed / rejected | Admin | الرفض بسبب إلزامي |
| confirmed | assigned | Admin | عاملة بلا عقد متداخل في نفس الفترة |
| assigned | active | System (Scheduler) أو Admin | تاريخ البدء وصل |
| active | completed | System | تاريخ الانتهاء وصل |
| active | terminated | Admin | باعتماد طلب إنهاء من العميل، أو قرار إداري بسبب |
| pending / confirmed / assigned | cancelled | Customer / Admin | قبل البدء فقط |

### استبدال العاملة ("إخراج" العاملة من العقد)
- **لا يغير حالة العقد** — العقد يبقى `active`، والذي يتغير هو **الإسناد**.
- العميل يرسل **طلب استبدال** من التطبيق: نوع المشكلة + وصف + (اختياري) مرفقات.
- الإدارة: `open → under_review → approved | rejected`.
- عند الاعتماد: يُغلق الإسناد الحالي (`ended_on`, `end_reason = replaced`) ويُنشأ إسناد جديد لعاملة بديلة؛ وتاريخ الإسنادات كاملاً محفوظ في `contract_assignments`.
- حد أقصى لعدد مرات الاستبدال خلال العقد = إعداد (الافتراضي: بلا حد، والإدارة تقرر).
- بين خروج العاملة ووصول البديلة قد توجد أيام بلا عاملة ← كيف تُحتسب؟ (سؤال 11).

### طلب إنهاء العقد
- نفس جدول الطلبات بنوع `terminate`، مع تاريخ الإنهاء المطلوب.
- عند الاعتماد: `active → terminated`، يُغلق الإسناد (`end_reason = terminated`)، ويُحسب المبلغ المستحق عن الأيام الفعلية (طريقة الاحتساب = إعداد، سؤال 11).

### الدفع في العقد
- عند كل نهاية شهر من العقد (أو عند الإنهاء) يُنشأ سجل `payments` بحالة `due` للفترة (`period_start`, `period_end`).
- الإدارة تسجل التحصيل (`collected`) — أو تعفي (`waived`) مع سبب وAudit.

---

## 6. دورة حياة الشكوى

```
open ──► under_review ──► resolved ──► closed
```
- الشكوى مرتبطة بالعميل و**إما** بزيارة **أو** بعقد (قيد: واحد بالضبط).
- السجل الزمني في `complaint_messages` (`message` / `status_change` / `internal_note`).
- **الفرق بين الشكوى وطلب الاستبدال:** الشكوى للتوثيق والمتابعة؛ طلب الاستبدال إجراء يغير العاملة. الإدارة تستطيع تحويل شكوى إلى طلب استبدال.

---

## 7. تصميم قاعدة البيانات (مبدئي — يُعتمد في Phase 2)

> ✚ = غير مذكور في SRS الأصلي، مقترح لتنفيذ متطلب قائم أو تغيير معتمد (CR).

### الحسابات
- **users:** `id, name, phone unique, email unique nullable, password, role enum(customer, worker), status, locale, phone_verified_at, timestamps, deleted_at`
- **customers:** `id, user_id FK unique, default_address_id FK nullable`
- **workers:** `id, user_id FK unique, type enum(cleaner, housekeeper) (CR-3), national_id_encrypted, status enum(active, inactive, on_leave), notes` — Index `status`, `type`
- **✚ teams (CR-3):** `id, name unique, leader_id FK→workers nullable, is_active, notes`
- **✚ team_members (CR-3):** `id, team_id FK, worker_id FK unique` — العضو في فريق واحد فقط
- **admin_users:** `id, name, email unique, password, role enum(super_admin, operations, support), is_active, last_login_at` (Guard منفصل)
- **addresses:** `id, customer_id FK, label, city, district, street, building, floor, details, latitude, longitude, deleted_at`

### الزيارات
- **services:** `id, name_ar, name_en, description_ar, description_en, icon, is_active, sort_order`
- **service_prices:** `id, service_id FK, label_ar, unit, amount decimal(10,2), currency, is_active, effective_from, effective_to`
- **bookings:** `id, booking_number unique, customer_id FK, address_id FK, address_snapshot JSON, status, scheduled_date, scheduled_time, subtotal, discount, total, currency, customer_notes, cancel_reason, cancelled_by_type, cancelled_at, confirmed_at, completed_at` — Index `(status, scheduled_date)`, `customer_id`
- **booking_items:** `id, booking_id FK, service_id FK, service_price_id FK, service_name_snapshot, quantity, unit_price, line_total`
- **booking_assignments** (يحل محل worker_assignments — CR-3): `id, booking_id FK, team_id FK, assigned_by FK→admin_users, status enum(pending, accepted, rejected, withdrawn), responded_by FK→workers (القائد), responded_at, rejection_reason` — Index `(team_id, status)`

### ✚ العقود (CR-2)
- **contract_plans:** `id, name_ar, name_en, description_ar, work_days_per_week, hours_per_day, monthly_price decimal(10,2), currency, min_months, max_months, is_active, sort_order`
  (مثال: "دوام كامل — 6 أيام، 8 ساعات يومياً")
- **contracts:** `id, contract_number unique, customer_id FK, plan_id FK, address_id FK, address_snapshot JSON, plan_snapshot JSON, start_date, end_date, months, monthly_price, total_amount, currency, status, terms_version, terms_accepted_at, customer_notes, confirmed_at, activated_at, ended_at, termination_reason, terminated_by FK→admin_users nullable`
  Index `(status, start_date)`, `(status, end_date)`, `customer_id`
- **contract_assignments:** `id, contract_id FK, worker_id FK, assigned_by FK→admin_users, started_on, ended_on nullable, end_reason enum(replaced, contract_completed, terminated, worker_unavailable) nullable`
  Index `(worker_id, ended_on)`, `contract_id` — قاعدة: إسناد مفتوح واحد (`ended_on IS NULL`) لكل عقد، ولا تداخل فترات للعاملة الواحدة (يُفرض في Service داخل Transaction مع قفل).
- **contract_change_requests:** `id, request_number unique, contract_id FK, customer_id FK, type enum(replace_worker, terminate), reason_type, details, requested_date nullable, status enum(open, under_review, approved, rejected), admin_response, handled_by FK→admin_users nullable, handled_at, resulting_assignment_id FK nullable`
  Index `(status, created_at)`, `contract_id`
- **✚ contract_change_request_attachments:** `id, change_request_id FK, path, mime, size`

### ✚ سجل الحالات (مشترك للزيارات والعقود)
- **status_logs:** `id, loggable_type, loggable_id, from_status, to_status, actor_type (customer/worker/admin/system), actor_id, note, created_at` — Index `(loggable_type, loggable_id)`

### الدفع — تحصيل نقدي (CR-1)
- **payments:** `id, booking_id FK nullable, contract_id FK nullable, period_start nullable, period_end nullable, amount, currency, method enum(cash) default cash, status enum(due, collected, waived), due_date, collected_at, collected_by FK→admin_users nullable, notes`
  قيد: `booking_id` أو `contract_id` (واحد بالضبط). Index `(status, due_date)`

### التقييمات والشكاوى
- **ratings:** `id, booking_id FK nullable unique, contract_id FK nullable, customer_id FK, worker_id FK nullable (للعقد), team_id FK nullable (للزيارة — CR-3), service_score, worker_score, comment, is_hidden` — قيد: زيارة أو عقد. للعقد: تقييم لكل خادمة عملت فيه (فريد على `contract_id + worker_id`).
- **complaints:** `id, complaint_number unique, customer_id FK, booking_id FK nullable, contract_id FK nullable, type, description, status, assigned_admin_id nullable, resolved_at, closed_at` — قيد: واحد بالضبط.
- **complaint_messages:** `id, complaint_id FK, sender_type, sender_id, kind enum(message, status_change, internal_note), body, meta JSON, created_at`
- **✚ complaint_attachments:** `id, complaint_id FK, complaint_message_id FK nullable, path, mime, size` (تخزين خاص + روابط موقّعة)

### النظام
- **notifications** (جدول Laravel القياسي) · **✚ device_tokens** `id, user_id FK, token unique, platform, last_used_at`
- **✚ settings** `key PK, value JSON, updated_by, updated_at`
- **audit_logs** `id, admin_user_id, action, auditable_type, auditable_id, old_values, new_values, ip, user_agent, created_at`

**يُسجل في Audit Log:** تأكيد/رفض/إلغاء زيارة أو عقد، الإسناد والسحب، **اعتماد/رفض طلبات الاستبدال والإنهاء**، **تسجيل التحصيل والإعفاء**، تعديل الأسعار والباقات، تعليق حساب، تغيير حالة شكوى، إخفاء تقييم، تعديل الإعدادات.

---

## 8. تصميم API (v1)

**Base:** `/api/v1` — JSON — `Accept-Language: ar|en`

```json
{ "data": { ... }, "meta": { "page": 1, "per_page": 20, "total": 57 } }
{ "message": "لا يمكن تنفيذ العملية", "code": "CONTRACT_INVALID_TRANSITION", "errors": { "field": ["..."] } }
```
`401` · `403` · `404` (غير موجود **أو لا يخصك**) · `409` تعارض حالة · `422` تحقق · `429` Rate limit.

### مشترك
`POST /auth/register` · `POST /auth/login` · `POST /auth/logout` · `GET /auth/me` · `POST /device-tokens` · `GET /notifications` · `POST /notifications/{id}/read` · `POST /notifications/read-all`

### العميل — الزيارات
| Method | Path | الوصف |
|---|---|---|
| GET | /services · /services/{id} | |
| GET/POST/PUT/DELETE | /addresses | |
| GET | /availability?service_id&date | الأوقات المتاحة |
| POST | /bookings/quote | حساب السعر في الخادم |
| POST | /bookings | إنشاء (Idempotency-Key) |
| GET | /bookings · /bookings/{id} | مع السجل الزمني وحالة الدفع |
| POST | /bookings/{id}/cancel | |
| POST | /bookings/{id}/rating | |

### العميل — العقود (CR-2)
| Method | Path | الوصف |
|---|---|---|
| GET | /contract-plans | الباقات الفعالة |
| POST | /contracts/quote | المدة + السعر الإجمالي + جدول الاستحقاقات |
| POST | /contracts | طلب عقد + قبول الشروط (`terms_version`) |
| GET | /contracts · /contracts/{id} | مع العاملة الحالية وسجل الإسنادات والاستحقاقات |
| POST | /contracts/{id}/cancel | قبل البدء فقط |
| GET/POST | /contracts/{id}/change-requests | `type: replace_worker \| terminate` + مرفقات |
| POST | /contracts/{id}/rating | عند نهاية العقد |

### العميل — الشكاوى
`GET/POST /complaints` (مع `booking_id` أو `contract_id`) · `GET /complaints/{id}` · `POST /complaints/{id}/messages`

### العاملة
| Method | Path | الوصف |
|---|---|---|
| GET | /worker/bookings?scope= · /worker/bookings/{id} | زيارات فريقه (`cleaner` فقط) |
| POST | /worker/bookings/{id}/accept · /reject | قائد الفريق فقط |
| POST | /worker/bookings/{id}/status | on_the_way → in_progress → completed — قائد الفريق فقط، بعد القبول |
| GET | /worker/contracts · /worker/contracts/{id} | عقود الخادمة (`housekeeper` فقط) — بيانات العميل خلال فترتها فقط |
| GET | /worker/ratings | |

> **حُذف (CR-1):** `POST /bookings/{id}/payments` و `POST /payments/webhook/{provider}`.

### الحماية
Sanctum مع انتهاء صلاحية، Rate limiting على الدخول والتسجيل، HTTPS فقط، فحص نوع وحجم المرفقات، `.env` خارج Git، `APP_DEBUG=false` في الإنتاج.

---

## 9. الاختبارات
- **Unit:** BookingStateMachine، ContractStateMachine، PricingService (بما فيها احتساب الإنهاء المبكر)، AvailabilityService (منع التداخل).
- **Feature — معايير القبول:** إنشاء زيارة كاملة؛ الإسناد؛ تحديث العاملة للحالة؛ التحصيل النقدي؛ **إنشاء عقد ← تأكيد ← إسناد ← تفعيل تلقائي**؛ **طلب استبدال ← اعتماد ← إسناد عاملة بديلة مع حفظ التاريخ**؛ **طلب إنهاء ← اعتماد ← احتساب المستحق**؛ التقييم؛ الشكاوى.
- **Authorization:** عميل يطلب عقد عميل آخر → 404؛ عاملة سابقة في عقد لا ترى بيانات العميل بعد انتهاء إسنادها؛ عاملة لديها عقد نشط لا يمكن إسنادها لزيارة متعارضة.

---

## 10. خطة التنفيذ

| Phase | المخرجات | التحقق |
|---|---|---|
| 2 | Migrations + Models + Enums + Seeders + ERD | `migrate:fresh --seed` |
| 3 | Auth + API الزيارات والعقود + State Machines + Policies + Scheduler | Feature tests خضراء |
| 4 | Filament: الطلبات، العقود، طلبات الاستبدال، التحصيل، الشكاوى، الخدمات والباقات، العاملات، الإعدادات، التقارير | اختبار يدوي + Audit |
| 5 | Flutter (يتطلب تثبيت Flutter SDK) | محاكي |
| 6 | FCM + الإشعارات المجدولة | اختبار تكامل |
| 7 | اكتمال الاختبارات + مراجعة أمنية | |
| 8 | OpenAPI + دليل التثبيت والنشر | |

---

## 11. قرارات مطلوبة من صاحب المشروع

حتى تُحسم، تُنفذ كـ **إعدادات** بقيمة افتراضية (بين قوسين).

### العقود (CR-2) — الأهم
1. **طبيعة الدوام:** دوام يومي بساعات محددة ثم تعود العاملة، أم **سكن لدى العميل**؟ (الافتراضي: دوام يومي)
2. **الباقات:** ما الباقات المطلوبة؟ (أيام العمل في الأسبوع، ساعات اليوم، السعر الشهري)
3. **المدد المتاحة:** شهر فقط، أم 1/3/6 أشهر؟ وهل يوجد تجديد؟ (الافتراضي: 1–12 شهراً، التجديد بطلب جديد)
4. **موعد الدفع في العقد:** نهاية كل شهر أم نهاية العقد كاملاً؟ (الافتراضي: نهاية كل شهر نقداً)
5. **الإنهاء المبكر:** يُدفع عن الأيام الفعلية فقط؟ أم يوجد إشعار مسبق (مثلاً 7 أيام) أو رسوم؟ (الافتراضي: الأيام الفعلية، بلا رسوم)
6. **الاستبدال:** كم مرة مسموح؟ خلال كم يوم تلتزم الوكالة بإرسال البديلة؟ وهل تُخصم أيام الانتظار من المبلغ؟ (الافتراضي: بلا حد، وتُخصم أيام الانتظار)
7. **غياب العاملة** (مرض/إجازة) خلال العقد: بديلة مؤقتة أم خصم؟ (الافتراضي: الإدارة تسند بديلة مؤقتة)
8. **هل يختار العميل العاملة بنفسه** من قائمة، أم الإدارة تختار؟ (الافتراضي: الإدارة)
9. **عقد مكتوب:** يكفي قبول الشروط داخل التطبيق، أم نحتاج ملف PDF للعقد يُرسل للعميل؟

### الدفع النقدي (CR-1)
10. **من يستلم النقد؟** العاملة أم مندوب الوكالة؟ ومن يسجّل التحصيل في النظام؟ (الافتراضي: الإدارة تسجله)

### عام
11. **السوق والعملة والمنطقة الزمنية** (التصاميم تستخدم عملة مثال).
12. **العمالة:** نساء فقط كما في الـ SRS، أم رجال ونساء؟
13. **نموذج تسعير الزيارات:** ثابت / بالساعة / حسب الغرف؟
14. **طريقة التسجيل:** كلمة مرور أم OTP؟ ومزود الـ SMS؟
15. **سياسة رفض العاملة لإسناد الزيارة**، و**سياسة إلغاء الزيارة** (الافتراضي: مسموح بسبب؛ الإلغاء حتى `assigned`).
16. **بيانات العاملة الظاهرة للعميل** (الافتراضي: الاسم الأول؛ في العقد: الاسم والصورة).
17. **إعادة فتح الشكوى بعد الحل؟** و**أنواع الشكاوى المعتمدة**.
18. **أدوار الإدارة:** تكفي ثلاثة (مدير عام / عمليات / خدمة عملاء)؟

### خارج النطاق الحالي
الدفع الإلكتروني (البنية تسمح بإضافته لاحقاً)، تتبع الموقع المباشر، المحادثة بين العميل والعاملة، العروض والخصومات، تعدد الفروع، الواجهة الإنجليزية (البنية جاهزة).
