<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractChangeRequestAttachment extends Model
{
    protected $guarded = ['id'];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ContractChangeRequest::class, 'change_request_id');
    }
}
