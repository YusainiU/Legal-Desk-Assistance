<?php

namespace Simplydigital\LegalDeskLawResources\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalCaseTreatment extends Model
{
    protected $fillable = [
        'citing_case_id', 'cited_case_id', 'treatment', 'supporting_passage', 'verification_status',
    ];

    public function citingCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'citing_case_id');
    }
}
