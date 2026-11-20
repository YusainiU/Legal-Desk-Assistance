<?php

namespace Simplydigital\LegalDeskLawResources\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    protected $fillable = [
        'title', 'court', 'decision_date', 'citation', 'jurisdiction', 'source_url',
        'source_type', 'source_checksum', 'verification_status', 'headnote', 'uploaded_by',
    ];

    protected $casts = ['decision_date' => 'date'];

    public function passages(): HasMany
    {
        return $this->hasMany(LegalCasePassage::class)->orderBy('sequence');
    }

    public function citations(): HasMany
    {
        return $this->hasMany(LegalCaseCitation::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(LegalCaseTreatment::class, 'cited_case_id');
    }
}
