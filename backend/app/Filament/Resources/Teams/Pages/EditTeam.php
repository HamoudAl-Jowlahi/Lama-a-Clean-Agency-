<?php

namespace App\Filament\Resources\Teams\Pages;

use App\Filament\Resources\Teams\TeamResource;
use App\Filament\Support\CallsServices;
use App\Services\TeamService;
use App\Support\AuditLogger;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTeam extends EditRecord
{
    use CallsServices;

    protected static string $resource = TeamResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['members'] = $this->record->members()->pluck('workers.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->callService(function ($admin) use ($record, $data) {
            $old = $record->only(['name', 'is_active']);
            $record->update(['name' => $data['name'], 'is_active' => (bool) $data['is_active'], 'notes' => $data['notes'] ?? null]);
            app(TeamService::class)->setMembers($record, array_map('intval', $data['members']), (int) $data['leader_id'], $admin);
            AuditLogger::log($admin, 'team.updated', $record, $old, $record->only(['name', 'is_active']));

            return $record;
        });
    }
}
