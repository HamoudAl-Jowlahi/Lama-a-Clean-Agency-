# لمعة — Backend

Laravel 12 · PHP 8.2+ · MySQL 8 / MariaDB 10.4+ (SQLite للاختبارات)

## التشغيل المحلي

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```

- للتجربة السريعة بدون MySQL: اجعل `DB_CONNECTION=sqlite` في `.env`.
- في بيئة `local` يُنشأ حساب إدارة تجريبي وبيانات تجريبية (انظر `database/seeders`).
- خارج `local`: عيّن `SEED_ADMIN_EMAIL` و `SEED_ADMIN_PASSWORD` قبل `db:seed`.

## لوحة الإدارة

`php artisan serve` ثم `http://127.0.0.1:8000/admin` — التفاصيل والأدوار في `../docs/04_phase4_admin.md`.

## أين تجد ماذا

| المسار | المحتوى |
|---|---|
| `app/Enums` | كل الحالات (زيارة، عقد، طلبات، دفع، شكاوى) — القيم نفسها في الـ API وتطبيق Flutter |
| `app/Models` | الـ Models والعلاقات والـ scopes |
| `app/Support/Settings.php` | القيم التجارية: جدول `settings` ثم `config/agency.php` |
| `config/agency.php` | القيم الافتراضية لكل قرار تجاري غير محسوم |
| `lang/{ar,en}/enums.php` | نصوص الحالات |

التوثيق الكامل في `../docs/`.
