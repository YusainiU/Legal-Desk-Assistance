<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedLegalResearch extends Model
{
    protected $fillable = ['user_id', 'legal_research_id', 'question', 'analysis', 'analysis_status'];

    public function research(): BelongsTo
    {
        return $this->belongsTo(LegalResearch::class, 'legal_research_id');
    }
}
