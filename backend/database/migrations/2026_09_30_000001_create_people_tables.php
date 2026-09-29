<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190)->unique();
            $table->string('password');
            $table->string('role', 30); // App\Enums\AdminRole
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // default_address_id يُضاف بعد إنشاء جدول addresses (علاقة دائرية)
            $table->timestamps();
        });

        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('national_id')->nullable(); // مشفّر عبر encrypted cast
            $table->string('status', 20)->default('active')->index(); // App\Enums\WorkerStatus
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('city', 80);
            $table->string('district', 120);
            $table->string('street', 160)->nullable();
            $table->string('building', 60)->nullable();
            $table->string('floor', 20)->nullable();
            $table->text('details')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('default_address_id')->nullable()->after('user_id')
                ->constrained('addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_address_id');
        });
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('workers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('admin_users');
    }
};
