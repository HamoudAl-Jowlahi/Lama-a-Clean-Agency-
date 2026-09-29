<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * المدير العام الأول. البيانات من .env (SEED_ADMIN_*) — لا توجد كلمة مرور في الكود.
 * في بيئة local فقط، إذا لم تُحدد القيم، يُنشأ حساب تطوير admin@lamaa.test / password.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            if (! app()->environment('local', 'testing')) {
                throw new RuntimeException('Set SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD before seeding outside local.');
            }
            $email = 'admin@lamaa.test';
            $password = 'password';
            $this->command?->warn("Dev admin: {$email} (password from AdminUserSeeder — local only)");
        }

        $admin = AdminUser::firstOrNew(['email' => $email]);
        $admin->fill(['name' => env('SEED_ADMIN_NAME', 'المدير العام'), 'password' => $password]);
        $admin->role = AdminRole::SuperAdmin;
        $admin->is_active = true;
        $admin->save();
    }
}
