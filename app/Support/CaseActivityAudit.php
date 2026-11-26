<?php

namespace App\Support;

use App\Models\Matter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CaseActivityAudit
{
    public static function record(Model $record, string $action, array $fields = []): void
    {
        DB::table('case_activity_audits')->insert([
            'actor_id' => auth()->id(),
            'matter_id' => $record instanceof Matter ? $record->getKey() : $record->getAttribute('matter_id'),
            'record_type' => class_basename($record),
            'record_id' => $record->getKey(),
            'action' => $action,
            'changed_fields' => json_encode(array_values($fields), JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);
    }
}
