<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load([
            'bookings' => fn ($q) => $q->latest('id')->limit(10),
            'contracts' => fn ($q) => $q->latest('id'),
            'addresses',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [CustomerResource::toggleAccount()];
    }
}
