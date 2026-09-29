<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintAttachment extends Model
{
    protected $guarded = ['id'];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ComplaintMessage::class, 'complaint_message_id');
    }
}
