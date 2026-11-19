<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matter extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'client', 'party', 'owner', 'practice', 'stage', 'status', 'checks', 'conflict'];

    protected $casts = [
        'checks' => 'array',
        'stage' => 'integer',
        'final_charge_minutes' => 'integer',
        'final_charge_minor' => 'integer',
    ];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    public function hasCompletedStage(int $stage): bool
    {
        return $stage >= 0 && ($stage < $this->stage || ($this->status === 'Closed' && $stage === $this->stage));
    }

    public function allowsContactLink(): bool
    {
        return $this->status === 'Open' || ! $this->contacts()->exists();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class)->latest('id');
    }

    public function tasks(): HasMany
    {
        return $this->entries()->where('kind', 'Task');
    }

    public function incompleteTasksThroughCurrentStage(): HasMany
    {
        return $this->tasks()->where('done', false)->where(function (Builder $query) {
            $query->whereNull('task_stage')->orWhere('task_stage', '<=', $this->stage);
        });
    }

    public function timeCharges(): HasMany
    {
        return $this->hasMany(CaseTimeCharge::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MatterEntry::class);
    }
}
