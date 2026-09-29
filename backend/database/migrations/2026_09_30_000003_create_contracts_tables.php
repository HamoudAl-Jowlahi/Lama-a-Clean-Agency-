<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CR-2: استئجار عاملة بعقد (شهر أو أكثر) + طلبات الاستبدال والإنهاء.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 120);
            $table->string('name_en', 120)->nullable();
            $table->text('description_ar')->nullable();
            $table->unsignedTinyInteger('work_days_per_week');
            $table->unsignedTinyInteger('hours_per_day');
            $table->decimal('monthly_price', 10, 2);
            $table->string('currency', 3);
            $table->unsignedTinyInteger('min_months')->default(1);
            $table->unsignedTinyInteger('max_months')->default(12);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 30)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained('contract_plans')->restrictOnDelete();
            $table->foreignId('address_id')->constrained()->restrictOnDelete();
            $table->json('address_snapshot');
            $table->json('plan_snapshot'); // الباقة وقت التعاقد — تعديل الباقة لا يغير العقود القائمة
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('months');
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3);
            $table->string('status', 20); // App\Enums\ContractStatus
            $table->string('terms_version', 20);
            $table->timestamp('terms_accepted_at');
            $table->text('customer_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->string('termination_reason', 255)->nullable();
            $table->foreignId('terminated_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'start_date']);
            $table->index(['status', 'end_date']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('contract_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('admin_users')->restrictOnDelete();
            $table->date('started_on');
            $table->date('ended_on')->nullable(); // NULL = الإسناد الحالي
            $table->string('end_reason', 30)->nullable(); // App\Enums\AssignmentEndReason
            $table->timestamps();

            $table->index(['worker_id', 'ended_on']);
            $table->index(['contract_id', 'ended_on']);
        });

        Schema::create('contract_change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 30)->nullable()->unique();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);        // App\Enums\ChangeRequestType
            $table->string('reason_type', 40);
            $table->text('details')->nullable();
            $table->date('requested_date')->nullable(); // تاريخ الإنهاء المطلوب
            $table->string('status', 20)->default('open'); // App\Enums\ChangeRequestStatus
            $table->text('admin_response')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('resulting_assignment_id')->nullable()->constrained('contract_assignments')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['contract_id', 'status']);
        });

        Schema::create('contract_change_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained('contract_change_requests')->cascadeOnDelete();
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_change_request_attachments');
        Schema::dropIfExists('contract_change_requests');
        Schema::dropIfExists('contract_assignments');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_plans');
    }
};
