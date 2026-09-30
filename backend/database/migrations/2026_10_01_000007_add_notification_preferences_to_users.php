<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6: تفضيلات إشعارات الجوال لكل مستخدم (شاشة الإعدادات في التطبيق).
 * {"orders": true, "complaints": true} — NULL = كل شيء مفعّل.
 * الإشعار يُحفظ داخل التطبيق دائماً؛ التفضيل يتحكم في الـ Push فقط.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
