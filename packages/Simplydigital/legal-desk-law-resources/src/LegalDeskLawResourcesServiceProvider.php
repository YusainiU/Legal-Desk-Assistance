<?php

namespace Simplydigital\LegalDeskLawResources;

use Illuminate\Support\ServiceProvider;

class LegalDeskLawResourcesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/legal-desk-law-resources.php', 'legal-desk-law-resources');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'legal-desk-law-resources');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/legal-desk-law-resources.php' => config_path('legal-desk-law-resources.php'),
        ], 'legal-desk-law-resources-config');
    }
}
