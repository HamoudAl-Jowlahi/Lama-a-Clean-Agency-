<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use App\Filament\Support\Admin;
use App\Models\AdminUser;
use App\Support\AuditLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdminUser extends CreateRecord
{
    protected static string $resource = AdminUserResource::class;

    /** role و is_active محميان من الإسناد الجماعي — يُضبطان صراحةً هنا فقط. */
    protected function handleRecordCreation(array $data): Model
    {
        $admin = new AdminUser(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $admin->forceFill(['role' => $data['role'], 'is_active' => (bool) ($data['is_active'] ?? true)])->save();

        AuditLogger::log(Admin::user(), 'admin_user.created', $admin, new: ['email' => $admin->email, 'role' => $admin->role->value]);

        return $admin;
    }
}
