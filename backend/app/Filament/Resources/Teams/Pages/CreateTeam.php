<?php

namespace App\Filament\Resources\Teams\Pages;

use App\Filament\Resources\Teams\TeamResource;
use App\Filament\Support\CallsServices;
use App\Services\TeamService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTeam extends CreateRecord
{
    use CallsServices;

    protected static string $resource = TeamResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->callService(function ($admin) use ($data) {
            $team = app(TeamService::class)->create($data['name'], array_map('intval', $data['members']), (int) $data['leader_id'], $admin);
            $team->update(['is_active' => (bool) $data['is_active'], 'notes' => $data['notes'] ?? null]);

            return $team;
        });
    }
}
