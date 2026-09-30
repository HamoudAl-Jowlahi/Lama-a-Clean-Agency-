<?php

namespace App\Filament\Resources\ChangeRequests\Pages;

use App\Filament\Resources\ChangeRequests\ChangeRequestActions;
use App\Filament\Resources\ChangeRequests\ChangeRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewChangeRequest extends ViewRecord
{
    protected static string $resource = ChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return ChangeRequestActions::all();
    }
}
