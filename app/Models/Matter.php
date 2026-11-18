<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matter extends Model
{
    protected $fillable = ['user_id', 'title', 'client', 'party', 'owner', 'practice', 'stage', 'status', 'checks', 'conflict'];

    protected $casts = ['checks' => 'array', 'stage' => 'integer'];

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class)->latest('id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MatterEntry::class);
    }
}
