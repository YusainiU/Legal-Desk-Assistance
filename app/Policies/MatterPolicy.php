<?php
namespace App\Policies;

use App\Models\Matter;
use App\Models\User;

class MatterPolicy
{
    public function viewAny(User $user): bool { return $user->has_access && ! $user->trashed(); }
    public function view(User $user, Matter $matter): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, Matter $matter): bool { return $this->view($user, $matter); }
    public function archive(User $user, Matter $matter): bool { return $this->update($user, $matter) && $matter->archived_at === null; }
    public function restore(User $user, Matter $matter): bool { return $this->update($user, $matter) && $matter->archived_at !== null; }
    public function delete(User $user, Matter $matter): bool { return false; }
}
