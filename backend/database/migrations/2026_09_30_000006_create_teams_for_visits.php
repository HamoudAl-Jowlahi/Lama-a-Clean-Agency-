<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CR-3: الزيارات تُسند لفرق ثابتة (قائد + أعضاء)، والخادمات للعقود فقط.
 * - workers.type: cleaner (عضو فريق زيارات) | housekeeper (خادمة — عقود)
 * - teams / team_members: العضو في فريق واحد على الأكثر
 * - booking_assignments يحل محل worker_assignments: الإسناد لفريق، والرد من القائد
 * - ratings.team_id: تقييم الزيارة للفريق
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->string('type', 20)->default('housekeeper')->after('user_id')->index(); // App\Enums\WorkerType
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->foreignId('leader_id')->nullable()->constrained('workers')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::dropIfExists('worker_assignments');

        Schema::create('booking_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('admin_users')->restrictOnDelete();
            $table->string('status', 20)->default('pending'); // App\Enums\AssignmentStatus
            $table->foreignId('responded_by')->nullable()->constrained('workers')->nullOnDelete(); // القائد الذي قبل/رفض
            $table->timestamp('responded_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['booking_id', 'status']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('worker_id')->constrained()->nullOnDelete();
            $table->index(['team_id', 'is_hidden']);
        });
    }

    public function down(): void
    {
        // MySQL: المفتاح الأجنبي أولاً، ثم الفهرس الذي يعتمد عليه، ثم العمود
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropIndex(['team_id', 'is_hidden']);
            $table->dropColumn('team_id');
        });

        Schema::dropIfExists('booking_assignments');

        Schema::create('worker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('admin_users')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();
            $table->index(['worker_id', 'status']);
            $table->index(['booking_id', 'status']);
        });

        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');

        Schema::table('workers', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
