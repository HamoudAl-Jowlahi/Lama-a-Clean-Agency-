<?php

namespace App\Services;

use App\Enums\WorkerType;
use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use App\Models\Team;
use App\Models\Worker;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * فرق الزيارات (CR-3). القواعد:
 * - كل الأعضاء من نوع cleaner، والعضو في فريق واحد فقط.
 * - القائد أحد أعضاء الفريق.
 */
class TeamService
{
    /** @param list<int> $memberIds */
    public function create(string $name, array $memberIds, int $leaderId, AdminUser $admin): Team
    {
        return DB::transaction(function () use ($name, $memberIds, $leaderId, $admin) {
            $team = Team::create(['name' => $name]);
            $this->setMembers($team, $memberIds, $leaderId, $admin, audit: false);
            AuditLogger::log($admin, 'team.created', $team, new: ['members' => $memberIds, 'leader_id' => $leaderId]);

            return $team;
        });
    }

    /** @param list<int> $memberIds */
    public function setMembers(Team $team, array $memberIds, int $leaderId, AdminUser $admin, bool $audit = true): Team
    {
        $memberIds = array_values(array_unique($memberIds));

        if (! in_array($leaderId, $memberIds, true)) {
            throw BusinessRuleException::make('TEAM_LEADER_NOT_MEMBER', status: 422);
        }

        $workers = Worker::whereKey($memberIds)->get();
        if ($workers->count() !== count($memberIds) || $workers->contains(fn (Worker $w) => $w->type !== WorkerType::Cleaner)) {
            throw BusinessRuleException::make('WORKER_TYPE_MISMATCH', status: 422);
        }

        $inOtherTeam = DB::table('team_members')->whereIn('worker_id', $memberIds)->where('team_id', '!=', $team->id)->exists();
        if ($inOtherTeam) {
            throw BusinessRuleException::make('WORKER_IN_OTHER_TEAM', status: 422);
        }

        return DB::transaction(function () use ($team, $memberIds, $leaderId, $admin, $audit) {
            $old = ['members' => $team->members()->pluck('workers.id')->all(), 'leader_id' => $team->leader_id];
            $team->members()->sync($memberIds);
            $team->update(['leader_id' => $leaderId]);

            if ($audit) {
                AuditLogger::log($admin, 'team.members_changed', $team, $old, ['members' => $memberIds, 'leader_id' => $leaderId]);
            }

            return $team;
        });
    }
}
