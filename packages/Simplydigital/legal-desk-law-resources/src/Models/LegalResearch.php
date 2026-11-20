<?php

namespace Simplydigital\LegalDeskLawResources\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalResearch extends Model
{
    protected $table = 'legal_research';

    protected $fillable = [
        'user_id', 'matter_id', 'question', 'court_filter', 'status',
        'analysis', 'analysis_status',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(config('legal-desk-law-resources.matter_model', \App\Models\Matter::class));
    }

    public function authorities(): HasMany
    {
        return $this->hasMany(LegalResearchAuthority::class)->orderBy('rank');
    }
}
