<?php

namespace App\Models;

use App\Enums\AssignmentEndReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** فترة عمل عاملة داخل عقد. ended_on = NULL يعني أنها العاملة الحالية. */
class ContractAssignment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
            'end_reason' => AssignmentEndReason::class,
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_by');
    }

    public function isCurrent(): bool
    {
        return $this->ended_on === null;
    }
}
