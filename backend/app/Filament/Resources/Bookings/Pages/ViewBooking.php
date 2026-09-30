<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingActions;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return parent::resolveRecord($key)->load('statusLogs');
    }

    protected function getHeaderActions(): array
    {
        return BookingActions::all();
    }
}
