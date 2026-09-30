<?php

namespace App\Filament\Resources\Workers\Pages;

use App\Filament\Resources\Workers\WorkerResource;
use App\Filament\Support\CallsServices;
use App\Services\StaffService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWorker extends EditRecord
{
    use CallsServices;

    protected static string $resource = WorkerResource::class;

    /** حقول الحساب من users، وحقول العمل من workers. كلمة المرور لا تُعرض أبداً. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record->user;

        return $data + ['name' => $user->name, 'phone' => $user->phone, 'email' => $user->email, 'password' => null];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->callService(fn ($admin) => app(StaffService::class)->updateWorker($record, $data, $admin));
    }
}
