<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Models\AdminUser;
use Tests\Feature\Api\ApiTestCase;

/**
 * اختبارات لوحة الإدارة (Filament): نفس بيئة اختبارات الـ API
 * (الوقت الثابت، الكتالوج) + مستخدم إدارة مسجل الدخول على guard admin.
 */
abstract class AdminTestCase extends ApiTestCase
{
    protected function actingAsAdmin(AdminRole $role = AdminRole::Operations): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => $role]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
