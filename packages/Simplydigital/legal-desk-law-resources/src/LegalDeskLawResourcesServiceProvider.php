<?php
namespace Simplydigital\LegalDeskLawResources;
use Illuminate\Support\ServiceProvider;
use Simplydigital\LegalDeskLawResources\Providers\AIServiceProvider;
class LegalDeskLawResourcesServiceProvider extends ServiceProvider {
 public function register(): void {
  $this->mergeConfigFrom(__DIR__.'/../config/legal-desk-law-resources.php', 'legal-desk-law-resources');
  $this->mergeConfigFrom(__DIR__.'/../config/ai-management.php', 'ai-management');
  $this->app->register(AIServiceProvider::class);
 }
 public function boot(): void {
  $this->loadViewsFrom(__DIR__.'/../resources/views', 'legal-desk-law-resources');
  $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
  $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
  $this->publishes([
   __DIR__.'/../config/legal-desk-law-resources.php' => config_path('legal-desk-law-resources.php'),
   __DIR__.'/../config/ai-management.php' => config_path('ai-management.php'),
  ], 'legal-desk-law-resources-config');
 }
}
