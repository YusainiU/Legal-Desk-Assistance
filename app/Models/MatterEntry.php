<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatterEntry extends Model
{
    protected $fillable = ['kind', 'body', 'assignee', 'due_on', 'done'];

    protected $casts = ['due_on' => 'date', 'done' => 'boolean'];
}
