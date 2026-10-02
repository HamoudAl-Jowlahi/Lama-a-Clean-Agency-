<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * النصوص الإنجليزية الناقصة: وصف الباقة، ونسخة إنجليزية من أسماء الخدمة والخيار المحفوظة في الطلب
 * (حتى يرى من يستخدم التطبيق بالإنجليزية طلباته بالإنجليزية).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->text('description_en')->nullable()->after('description_ar');
        });

        Schema::table('booking_items', function (Blueprint $table) {
            $table->string('service_name_en_snapshot', 160)->nullable()->after('service_name_snapshot');
            $table->string('price_label_en_snapshot', 160)->nullable()->after('price_label_snapshot');
        });

        // الطلبات السابقة: نأخذ الاسم الإنجليزي الحالي (إن وُجد)
        DB::table('booking_items')->orderBy('id')->each(function ($item) {
            DB::table('booking_items')->where('id', $item->id)->update([
                'service_name_en_snapshot' => DB::table('services')->where('id', $item->service_id)->value('name_en'),
                'price_label_en_snapshot' => DB::table('service_prices')->where('id', $item->service_price_id)->value('label_en'),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropColumn(['service_name_en_snapshot', 'price_label_en_snapshot']);
        });

        Schema::table('contract_plans', function (Blueprint $table) {
            $table->dropColumn('description_en');
        });
    }
};
