<?php

namespace App\Filament\Resources\Workers\Pages;

use App\Filament\Resources\Workers\WorkerResource;
use App\Filament\Support\CallsServices;
use App\Services\StaffService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWorker extends CreateRecord
{
    use CallsServices;

    protected static string $resource = WorkerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->callService(fn ($admin) => app(StaffService::class)->createWorker($data, $admin));
    }
}
