<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('active-users', function (Application $app, array $config): EloquentUserProvider {
            return (new EloquentUserProvider($app['hash'], $config['model']))
                ->withQuery(fn (Builder $query) => $query->where('has_access', true));
        });

        Gate::define('manage-users', fn (User $user): bool => $user->has_access && $user->is_admin);
    }
}
