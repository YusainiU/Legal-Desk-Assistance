<?php

namespace App\Observers;

use App\Support\CaseActivityAudit;
use Illuminate\Database\Eloquent\Model;

final class CaseActivityObserver
{
    public function created(Model $record): void
    {
        CaseActivityAudit::record($record, 'created');
    }

    public function updated(Model $record): void
    {
        $fields = array_values(array_diff(array_keys($record->getChanges()), ['updated_at']));
        if ($fields !== []) {
            CaseActivityAudit::record($record, 'updated', $fields);
        }
    }

    public function deleted(Model $record): void
    {
        CaseActivityAudit::record($record, 'deleted');
    }
}
