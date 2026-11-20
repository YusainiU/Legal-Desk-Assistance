<?php

namespace Simplydigital\LegalDeskLawResources\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalCasePassage extends Model
{
    protected $fillable = ['legal_case_id', 'sequence', 'paragraph_label', 'content'];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}
