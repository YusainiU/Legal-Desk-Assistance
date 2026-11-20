<?php

namespace Simplydigital\LegalDeskLawResources\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalResearchAuthority extends Model
{
    protected $fillable = [
        'legal_case_id', 'legal_case_passage_id', 'rank',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(LegalCasePassage::class, 'legal_case_passage_id');
    }
}
