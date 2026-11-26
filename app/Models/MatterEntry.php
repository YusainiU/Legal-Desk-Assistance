<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterEntry extends Model
{
    protected $fillable = ['kind', 'team_id', 'body', 'assignee', 'due_on', 'due_at', 'done', 'task_stage'];

    protected $casts = ['due_on' => 'date', 'due_at' => 'datetime', 'done' => 'boolean', 'task_stage' => 'integer', 'team_id' => 'integer'];
    public function deadline(): ?\Illuminate\Support\Carbon
    {
        return $this->due_at ?? $this->due_on?->endOfDay();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
