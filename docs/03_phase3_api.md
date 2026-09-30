# لمعة — Phase 3: الـ API

> الحالة: **مكتمل ومُتحقق منه** — 53 اختباراً (440 تحققاً) تنجح على SQLite و MariaDB 10.4.
> لوحة الإدارة (Phase 4) تستخدم نفس الـ Services مباشرة، لذلك لا يوجد API منفصل للإدارة.

## الأساسيات

| | |
|---|---|
| **Base URL** | `https://<host>/api/v1` |
| **المصادقة** | `Authorization: Bearer <token>` (Sanctum) — صلاحية الـ token 30 يوماً |
| **اللغة** | `Accept-Language: ar` (افتراضي) أو `en` — تؤثر على الرسائل ونصوص الحالات |
| **الصيغة** | JSON. التواريخ `Y-m-d`، الأوقات `H:i`، المبالغ نص بخانتين `"250.00"` |
| **منع التكرار** | `Idempotency-Key: <uuid>` على `POST /bookings` و `POST /contracts` — إعادة الإرسال ترجع نفس الرد |
| **حدود الطلبات** | الدخول/التسجيل: 5 في الدقيقة لكل رقم + IP · باقي الـ API: 120 في الدقيقة |

### شكل الرد
```json
{ "data": { ... } }
{ "data": [ ... ], "links": { ... }, "meta": { "current_page": 1, "per_page": 20, "total": 57 } }
```

### شكل الخطأ (موحد لكل الأخطاء)
```json
{ "message": "لا يمكن الانتقال من \"قيد المراجعة\" إلى \"مكتمل\".", "code": "INVALID_TRANSITION" }
{ "message": "الاسم مطلوب.", "code": "VALIDATION_FAILED", "errors": { "name": ["الاسم مطلوب."] } }
```

| HTTP | code | المعنى |
|---|---|---|
| 401 | `UNAUTHENTICATED` · `INVALID_CREDENTIALS` | لم يسجل الدخول / بيانات خاطئة |
| 403 | `FORBIDDEN` · `ACCOUNT_SUSPENDED` · `ASSIGNMENT_REJECT_NOT_ALLOWED` | دور خاطئ / حساب موقوف / السياسة تمنع |
| 404 | `NOT_FOUND` | غير موجود **أو لا يخصك** (لا نكشف وجود بيانات غيرك) |
| 409 | `INVALID_TRANSITION` · `SLOT_UNAVAILABLE` · `CANCEL_TOO_LATE` · `REQUEST_ALREADY_OPEN` · `ALREADY_RATED` · ... | قاعدة عمل تمنع العملية |
| 422 | `VALIDATION_FAILED` · `TERMS_OUTDATED` · `CONTRACT_START_TOO_SOON` · ... | مدخلات غير صحيحة |
| 429 | `TOO_MANY_REQUESTS` | تجاوز حد المحاولات |

القائمة الكاملة للأكواد ورسائلها: `backend/lang/ar/api.php`.

### الحالات (القيم ثابتة — تطبيق Flutter يعتمد عليها)
كل حالة تُرجع كـ `{"value": "on_the_way", "label": "في الطريق"}`.

- **الزيارة:** `pending` → `confirmed` → `assigned` → `on_the_way` → `in_progress` → `completed` · بدائل: `cancelled`, `rejected`
- **العقد:** `pending` → `confirmed` → `assigned` → `active` → `completed` · بدائل: `terminated`, `cancelled`, `rejected`
- **طلب الاستبدال/الإنهاء:** `open` → `under_review` → `approved` | `rejected`
- **الدفع (نقداً):** `due` → `collected` | `waived`
- **الشكوى:** `open` → `under_review` → `resolved` → `closed`

---

## 1. الحساب (عميل + عاملة)

| Method | Path | الوصف |
|---|---|---|
| POST | `/auth/register` | تسجيل **عميل** `{name, phone, password, email?, device_name?}` → `{token, user}` |
| POST | `/auth/login` | `{phone, password}` → `{token, user}` — الدور (`user.role`) يحدد واجهة التطبيق |
| POST | `/auth/logout` | إلغاء الـ token الحالي |
| GET | `/auth/me` | بيانات المستخدم + `default_address_id` (للعميل) + `worker {type, team {name, is_leader}}` (للموظف) |
| PATCH | `/auth/me` | `{name?, email?, locale?, notification_preferences?: {orders, complaints}}` |
| POST | `/auth/password` | `{current_password, password}` — ينهي الجلسات على الأجهزة الأخرى |
| GET | `/notifications` | الإشعارات `{event, title, body, subject, read}` + `meta.unread` — التفاصيل في [docs/05](05_phase6_notifications.md) |
| POST | `/notifications/{id}/read` · `/notifications/read-all` | |
| POST · DELETE | `/device-tokens` | `{token, platform: android\|ios}` لإشعارات FCM (Phase 6) |

> حسابات العاملات تُنشأ من لوحة الإدارة فقط — لا تسجيل ذاتي للعاملة.

## 2. الكتالوج (بدون تسجيل دخول)

| Method | Path | الوصف |
|---|---|---|
| GET | `/services` · `/services/{id}` | الخدمات مع `prices[]` و `starting_price` |
| GET | `/contract-plans` | باقات العقود + `meta.terms_version` (يُرسل عند طلب العقد) |

## 3. العميل (`role: customer`)

### العناوين
`GET /addresses` · `POST /addresses` · `PUT /addresses/{id}` · `DELETE /addresses/{id}`
الحقول: `label, city, district, street?, building?, floor?, details?, latitude?, longitude?, make_default?`

### الزيارات
| Method | Path | الوصف |
|---|---|---|
| GET | `/availability?date=2026-10-05` | `slots: [{time: "10:00", available: true}, ...]` |
| POST | `/bookings/quote` | `{service_price_id, quantity?}` → السعر والضريبة والإجمالي |
| POST | `/bookings` | `{service_price_id, quantity?, address_id, scheduled_date, scheduled_time, customer_notes?}` |
| GET | `/bookings?status=` | قائمة الزيارات |
| GET | `/bookings/{id}` | التفاصيل + `timeline[]` + `team.name` (الفريق المنفذ) + `payment` + `can_rate` |
| POST | `/bookings/{id}/cancel` | `{reason?}` — حتى حالة `assigned` وقبل الموعد بـ 6 ساعات (إعدادات) |
| POST | `/bookings/{id}/rating` | `{service_score 1-5, worker_score?, comment?}` — بعد `completed`، مرة واحدة |

### العقود (استئجار عاملة)
| Method | Path | الوصف |
|---|---|---|
| POST | `/contracts/quote` | `{plan_id, start_date, months}` → تاريخ الانتهاء والإجمالي |
| POST | `/contracts` | `{plan_id, address_id, start_date, months, accept_terms: true, terms_version, customer_notes?}` |
| GET | `/contracts` · `/contracts/{id}` | + `current_worker`, `workers_history[]`, `progress {day, total_days}`, `payments[]`, `change_requests[]`, `timeline[]` |
| POST | `/contracts/{id}/cancel` | قبل البدء فقط |
| GET | `/contracts/{id}/change-requests` | طلبات الاستبدال/الإنهاء |
| POST | `/contracts/{id}/change-requests` | **استبدال العاملة:** `{type: "replace_worker", reason_type, details?, attachments[]?}` · **إنهاء العقد:** `{type: "terminate", reason_type, requested_date}` (multipart عند وجود مرفقات) |
| POST | `/contracts/{id}/rating` | `{worker_id, service_score, worker_score?, comment?}` — بعد انتهاء العقد، لكل عاملة عملت فيه |

`reason_type`: `frequent_delay` · `quality` · `absence` · `behavior` · `no_longer_needed` · `other`

### الشكاوى
| Method | Path | الوصف |
|---|---|---|
| GET | `/complaints` · `/complaints/{id}` | + `messages[]` (رسائل وتغييرات حالة — **بدون** الملاحظات الداخلية للإدارة) |
| POST | `/complaints` | `{booking_id \| contract_id, type, description, attachments[]?}` |
| POST | `/complaints/{id}/messages` | `{body, attachments[]?}` |

`type`: `late` · `quality` · `behavior` · `payment` · `other`

## 4. الموظفون (`role: worker`) — CR-3

نوعان منفصلان، والتطبيق يقرأ النوع من `GET /auth/me` → `data.worker.type.value` ويعرض الواجهة المناسبة:

| | `cleaner` — فريق الزيارات | `housekeeper` — خادمة |
|---|---|---|
| يرى | زيارات **فريقه** | **عقوده** |
| يتصرف | **القائد فقط** (`data.worker.team.is_leader`) | لا حالات تنفيذ |
| مسارات الآخر | `403 FORBIDDEN` | `403 FORBIDDEN` |

### فريق الزيارات (`cleaner`)
| Method | Path | الوصف |
|---|---|---|
| GET | `/worker/bookings?scope=new\|today\|upcoming\|done` | زيارات الفريق فقط — كل الأعضاء يرونها |
| GET | `/worker/bookings/{id}` | `team`، `is_leader`، `customer.name` (الاسم الأول)، `address`، `customer_notes`. **للقائد فقط:** `customer.phone` (أثناء `on_the_way`/`in_progress`)، `amount_to_collect`، `next_statuses[]` |
| POST | `/worker/bookings/{id}/accept` | **القائد:** قبول الإسناد (خطوة صريحة ومستقلة) |
| POST | `/worker/bookings/{id}/reject` | **القائد:** `{reason}` — يعود الطلب للإدارة (`confirmed`) |
| POST | `/worker/bookings/{id}/status` | **القائد:** `{status: on_the_way \| in_progress \| completed}` — خطوة واحدة في كل مرة، وبعد القبول فقط (`ASSIGNMENT_NOT_ACCEPTED` قبله) |

العضو غير القائد → `403 TEAM_LEADER_ONLY` على الإجراءات. فريق آخر → `404`.

### الخادمة (`housekeeper`)
| Method | Path | الوصف |
|---|---|---|
| GET | `/worker/contracts` · `/worker/contracts/{id}` | `is_current` — العنوان والهاتف والملاحظات تظهر **فقط** خلال فترة عملها الحالية |

### مشترك
| Method | Path | الوصف |
|---|---|---|
| GET | `/worker/ratings` | الخادمة: تقييماتها · عضو الفريق: تقييمات فريقه — بدون اسم العميل، + `meta.average` |

---

## أمثلة

**تسجيل الدخول**
```http
POST /api/v1/auth/login
Content-Type: application/json
Accept-Language: ar

{ "phone": "0551234567", "password": "********" }
```
```json
{ "data": { "token": "1|Xy...", "token_type": "Bearer", "expires_at": "2026-11-03T09:00:00+00:00",
  "user": { "id": 7, "name": "سارة أحمد", "role": { "value": "customer", "label": "عميل" } } } }
```

**طلب استبدال العاملة**
```http
POST /api/v1/contracts/14/change-requests
Authorization: Bearer 1|Xy...

{ "type": "replace_worker", "reason_type": "quality", "details": "المطبخ لا يُنظف بشكل جيد" }
```
```json
{ "data": { "number": "RQ-2026-000031", "type": { "value": "replace_worker", "label": "استبدال العاملة" },
  "status": { "value": "open", "label": "جديد" } } }
```

---

## منطق العمل (أين يوجد)

| الموضوع | الملف |
|---|---|
| انتقالات الحالة ومن يملكها | `app/StateMachines/{Booking,Contract}StateMachine.php` |
| إنشاء/إلغاء/إسناد الزيارة، تقدم العاملة، استحقاق الدفع عند الإتمام | `app/Services/BookingService.php` |
| العقود: الطلب، التأكيد، الإسناد، التفعيل والانتهاء التلقائي، الإنهاء المبكر | `app/Services/ContractService.php` |
| استبدال العاملة وطلبات الإنهاء | `app/Services/ContractChangeService.php` |
| الاستحقاقات الشهرية، خصم أيام انتظار البديلة، احتساب الإنهاء المبكر | `app/Services/ContractBillingService.php` |
| توفر الفرق والخادمات، والأوقات المتاحة | `app/Services/AvailabilityService.php` |
| فرق الزيارات: الأعضاء والقائد (CR-3) | `app/Services/TeamService.php` |
| التحصيل النقدي، الشكاوى، التقييم | `CollectionService` · `ComplaintService` · `RatingService` |

### المهمة اليومية
`php artisan lamaa:contracts-daily` — تُجدول 00:10 بتوقيت الوكالة: تفعيل العقود في تاريخ بدئها، إنهاء المنتهية، وإنشاء الاستحقاقات النقدية. آمنة للتكرار.
في الخادم يلزم cron واحد: `* * * * * php artisan schedule:run`.

### أمثلة على قواعد مُختبرة
- عقد شهر (31 يوماً) استُبدلت عاملته مع يوم انتظار واحد → المستحق `1800 × 30/31 = 1741.94`.
- إنهاء مبكر بعد 21 يوماً → `1800 × 21/31 = 1219.35`، ويُنشأ الاستحقاق بعد آخر يوم عمل.
- الطاقة الاستيعابية للزيارات = عدد الفرق المفعّلة؛ الفريق لا يأخذ زيارتين متداخلتين (`TEAM_BUSY`).
- الخادمة لا تُسند لعقدين متداخلين (`WORKER_BUSY_CONTRACT`)، وعضو فريق الزيارات لا يُسند لعقد (`WORKER_TYPE_MISMATCH`).
- العميل لا يستطيع تحديد السعر أو الحالة — أي `total` أو `status` مرسل يُتجاهل.
