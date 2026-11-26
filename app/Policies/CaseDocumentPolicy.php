<?php
namespace App\Policies;

use App\Models\CaseDocument;
use App\Models\Matter;
use App\Models\User;

class CaseDocumentPolicy
{
    public function view(User $user, CaseDocument $document): bool
    {
        return $document->matter !== null && $user->can('view', $document->matter);
    }

    public function preview(User $user, CaseDocument $document): bool { return $this->view($user, $document); }
    public function download(User $user, CaseDocument $document): bool { return $this->view($user, $document); }
    public function create(User $user, Matter $matter): bool { return $user->can('update', $matter) && $matter->status === 'Open'; }
    public function delete(User $user, CaseDocument $document): bool
    {
        return $document->matter !== null && $this->create($user, $document->matter);
    }
}
