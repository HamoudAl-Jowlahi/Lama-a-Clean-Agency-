<?php

namespace App\Filament\Resources\Contracts\Pages;

use App\Filament\Resources\Contracts\ContractActions;
use App\Filament\Resources\Contracts\ContractResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['assignments.worker.user', 'payments', 'changeRequests', 'statusLogs']);
    }

    protected function getHeaderActions(): array
    {
        return ContractActions::all();
    }
}
