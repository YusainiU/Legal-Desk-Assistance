<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterEntry extends Model
{
    protected $fillable = ['kind', 'team_id', 'body', 'assignee', 'due_on', 'done', 'task_stage'];

    protected $casts = ['due_on' => 'date', 'done' => 'boolean', 'task_stage' => 'integer', 'team_id' => 'integer'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
