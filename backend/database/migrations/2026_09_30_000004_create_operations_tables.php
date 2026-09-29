<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * سجل الحالات، التحصيل النقدي (CR-1)، التقييمات، الشكاوى.
 * الجداول المرتبطة بزيارة أو عقد تفرض "واحد بالضبط" عبر CHECK في MySQL/MariaDB،
 * وعبر الـ Model في كل المحركات (SQLite لا يدعم إضافة CHECK بعد الإنشاء).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('loggable'); // Booking أو Contract
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('actor_type', 20); // App\Enums\ActorType
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('period_start')->nullable(); // للعقود: فترة الاستحقاق
            $table->date('period_end')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('method', 20)->default('cash'); // App\Enums\PaymentMethod
            $table->string('status', 20)->default('due');  // App\Enums\PaymentStatus
            $table->date('due_date');
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('service_score');
            $table->unsignedTinyInteger('worker_score')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->unique(['contract_id', 'worker_id']); // تقييم لكل عاملة عملت في العقد
            $table->index(['worker_id', 'is_hidden']);
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number', 30)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->text('description');
            $table->string('status', 20)->default('open'); // App\Enums\ComplaintStatus
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('customer_id');
        });

        Schema::create('complaint_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type', 20); // App\Enums\ActorType
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('kind', 20)->default('message'); // App\Enums\ComplaintMessageKind
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['complaint_id', 'created_at']);
        });

        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('complaint_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        if (DB::getDriverName() !== 'sqlite') {
            foreach (['payments', 'complaints'] as $t) {
                DB::statement("ALTER TABLE {$t} ADD CONSTRAINT {$t}_one_subject_chk CHECK ((booking_id IS NULL) <> (contract_id IS NULL))");
            }
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_one_subject_chk CHECK ((booking_id IS NULL) <> (contract_id IS NULL))');
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_scores_chk CHECK (service_score BETWEEN 1 AND 5 AND (worker_score IS NULL OR worker_score BETWEEN 1 AND 5))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_attachments');
        Schema::dropIfExists('complaint_messages');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('status_logs');
    }
};
