<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseTimeCharge extends Model
{
    protected $fillable = ['stage', 'description', 'minutes', 'hourly_rate_minor', 'amount_minor'];

    protected $casts = [
        'stage' => 'integer',
        'minutes' => 'integer',
        'hourly_rate_minor' => 'integer',
        'amount_minor' => 'integer',
    ];
}
