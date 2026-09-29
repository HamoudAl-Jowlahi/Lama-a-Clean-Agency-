<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** قيمة معدّلة من لوحة الإدارة تتجاوز القيمة الافتراضية في config/agency.php. */
class Setting extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
