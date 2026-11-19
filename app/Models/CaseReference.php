<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseReference extends Model
{
    public const TYPES = [
        'statute' => 'Statute',
        'statutory_provision' => 'Statutory provision',
        'constitutional_provision' => 'Constitutional provision',
        'clause' => 'Legal or contract clause',
        'precedent' => 'Case precedent',
        'regulation' => 'Regulation or rule',
        'other' => 'Other authority',
    ];

    protected $fillable = ['type', 'title', 'citation', 'jurisdiction', 'source_url', 'applicability'];
    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
