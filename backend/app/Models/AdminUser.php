<?php

namespace App\Models;

use App\Enums\AdminRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * مستخدم لوحة الإدارة — Guard منفصل (admin) عن حسابات التطبيق.
 * الصلاحيات في AdminRole::can().
 */
class AdminUser extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'role' => AdminRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }

    /** صلاحية من خريطة AdminRole (مثل "bookings.manage"). */
    public function hasAbility(string $ability): bool
    {
        return $this->is_active && $this->role->can($ability);
    }

    /** الحساب الموقوف لا يدخل اللوحة حتى لو كانت كلمة المرور صحيحة. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
