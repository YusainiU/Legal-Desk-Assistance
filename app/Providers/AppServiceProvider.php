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
    public function register(): void
    {
        config(['legal-desk-law-resources.page_middleware' => [\App\Http\Middleware\EnforcePageAccess::class]]);
    }

    public function boot(): void
    {
        Auth::provider('active-users', function (Application $app, array $config): EloquentUserProvider {
            return (new EloquentUserProvider($app['hash'], $config['model']))
                ->withQuery(fn (Builder $query) => $query->where('has_access', true));
        });

        Gate::policy(\App\Models\Matter::class, \App\Policies\MatterPolicy::class);
        Gate::policy(\App\Models\CaseDocument::class, \App\Policies\CaseDocumentPolicy::class);

        \Livewire\Livewire::addPersistentMiddleware([\App\Http\Middleware\EnforcePageAccess::class]);
        Gate::define('access-page', fn (User $user, string $page): bool => \App\Support\PageAccess::allows($user, $page));
        Gate::define('manage-page-permissions', fn (User $user): bool => $user->has_access && ! $user->trashed() && $user->is_super_admin);
        Gate::define('view-login-audit', fn (User $user): bool => $user->has_access && $user->is_super_admin);
        Gate::define('manage-users', fn (User $user): bool => $user->has_access && ($user->is_super_admin || $user->is_admin));
        Gate::define('manage-user', fn (User $actor, User $target): bool => $actor->has_access
            && ($actor->is_super_admin || $actor->is_admin)
            && ($actor->is($target) || (! $target->is_super_admin && ($actor->is_super_admin || ! $target->is_admin))));
        Gate::define('grant-admin', fn (User $user): bool => $user->has_access && $user->is_super_admin);
        Gate::define('update-firm-settings', fn (User $user): bool => $user->has_access && $user->is_super_admin);
    }
}
