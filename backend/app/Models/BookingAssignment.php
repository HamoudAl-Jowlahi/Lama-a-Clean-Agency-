<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** إسناد زيارة لفريق (CR-3). القائد يقبل أو يرفض. */
class BookingAssignment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_by');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'responded_by');
    }
}
