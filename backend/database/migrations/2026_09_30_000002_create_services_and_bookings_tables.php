<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 120);
            $table->string('name_en', 120)->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable(); // المدة التقريبية
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('label_ar', 120);
            $table->string('label_en', 120)->nullable();
            $table->string('unit', 20)->default('fixed'); // App\Enums\PriceUnit
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'is_active']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 30)->nullable()->unique(); // يُملأ بعد الإنشاء من الـ id (HasDocumentNumber)
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('address_id')->constrained()->restrictOnDelete();
            $table->json('address_snapshot'); // العنوان وقت الطلب — تكرار مقصود
            $table->string('status', 20);     // App\Enums\BookingStatus
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3);
            $table->text('customer_notes')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->string('cancelled_by_type', 20)->nullable(); // App\Enums\ActorType
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_date']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_price_id')->constrained()->restrictOnDelete();
            $table->string('service_name_snapshot', 160);
            $table->string('price_label_snapshot', 160);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        Schema::create('worker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('admin_users')->restrictOnDelete();
            $table->string('status', 20)->default('pending'); // App\Enums\AssignmentStatus
            $table->timestamp('responded_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['worker_id', 'status']);
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_assignments');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('service_prices');
        Schema::dropIfExists('services');
    }
};
