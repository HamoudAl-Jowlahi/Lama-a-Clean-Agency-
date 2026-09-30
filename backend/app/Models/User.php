<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * حساب تطبيق الجوال: عميل أو عاملة. role و status لا يُسندان جماعياً
 * حتى لا يستطيع أي طلب من التطبيق تغيير صلاحياته.
 */
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /** فئات إشعارات الجوال التي يتحكم بها المستخدم من شاشة الإعدادات. */
    public const PUSH_CATEGORIES = ['orders', 'complaints'];

    protected $fillable = ['name', 'phone', 'email', 'password', 'locale', 'notification_preferences'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'status' => 'active',
        'locale' => 'ar',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    /** الإشعارات تُكتب بلغة المستخدم (ar/en). */
    public function preferredLocale(): string
    {
        return $this->locale ?: config('app.locale');
    }

    /** هل يريد Push لهذه الفئة؟ غير المحدد = مفعّل. قائمة الإشعارات داخل التطبيق لا تتأثر. */
    public function wantsPush(string $category): bool
    {
        return (bool) ($this->notification_preferences[$category] ?? true);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function worker(): HasOne
    {
        return $this->hasOne(Worker::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isWorker(): bool
    {
        return $this->role === UserRole::Worker;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
