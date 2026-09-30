<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use App\Filament\Support\Admin;
use App\Support\AuditLogger;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return ['password' => null] + $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $old = ['role' => $record->role->value, 'is_active' => $record->is_active];

        $record->fill(array_intersect_key($data, array_flip(['name', 'email', 'password'])));
        $attributes = ['role' => $data['role']];
        if (! $record->is(Admin::user())) {
            $attributes['is_active'] = (bool) $data['is_active']; // لا يوقف المدير نفسه
        }
        $record->forceFill($attributes)->save();

        AuditLogger::log(Admin::user(), 'admin_user.updated', $record, $old, [
            'role' => $record->role->value, 'is_active' => $record->is_active, 'password_changed' => filled($data['password'] ?? null),
        ]);

        return $record;
    }
}
