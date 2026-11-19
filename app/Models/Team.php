<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class Team extends Model
{
    protected $fillable = ['name', 'description'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function openCases(): HasMany
    {
        return $this->hasMany(Matter::class)->where('status', 'Open');
    }

    public function openTasks(): HasMany
    {
        return $this->hasMany(MatterEntry::class)
            ->where('kind', 'Task')->where('done', false)
            ->whereHas('matter', fn (Builder $query) => $query->where('status', 'Open'));
    }

    public static function availableTo(User $user): Builder
    {
        return static::query()->unless(Gate::forUser($user)->allows('manage-users'),
            fn (Builder $query) => $query->whereHas('members', fn (Builder $members) => $members->whereKey($user->id)));
    }

    public static function assignmentOptions(User $user, ?int $assignedId = null)
    {
        $teams = static::availableTo($user);

        if ($assignedId !== null) {
            $teams->orWhere('teams.id', $assignedId);
        }

        return $teams->orderBy('name')->get(['id', 'name']);
    }

    public static function assignmentId(User $user, int|string|null $value, string $field, ?int $assignedId = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;
        if ($id !== $assignedId && ! static::availableTo($user)->whereKey($id)->exists()) {
            throw ValidationException::withMessages([$field => 'Choose a team available to you.']);
        }

        return $id;
    }
}
