<?php

namespace MacCesar\LaravelDropzoneEnhanced;

use Illuminate\Support\ServiceProvider;

class DropzoneServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    // Merge configuration with user's
    $this->mergeConfigFrom(
      __DIR__ . '/../config/dropzone.php',
      'dropzone'
    );

    if ($this->app->runningInConsole()) {
      $this->commands([
        \MacCesar\LaravelDropzoneEnhanced\Console\Commands\InstallDropzoneEnhanced::class,
        \MacCesar\LaravelDropzoneEnhanced\Console\Commands\CheckDropzoneUpdate::class,
      ]);
    }
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // Load routes
    $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

    // Load views
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dropzone-enhanced');

    // Load translations
    $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'dropzone-enhanced');

    // Load migrations automatically
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

    // Also publish migrations for customization if needed
    if ($this->app->runningInConsole()) {
      $this->publishes([
        __DIR__ . '/../database/migrations' => database_path('migrations'),
      ], 'dropzone-enhanced-migrations');

      // Publish configuration
      $this->publishes([
        __DIR__ . '/../config/dropzone.php' => config_path('dropzone.php'),
      ], 'dropzone-enhanced-config');

      // Publish views
      $this->publishes([
        __DIR__ . '/../resources/views' => resource_path('views/vendor/dropzone-enhanced'),
      ], 'dropzone-enhanced-views');

      // Publish translations
      $this->publishes([
        __DIR__ . '/../resources/lang' => resource_path('lang/vendor/dropzone-enhanced'),
      ], 'dropzone-enhanced-lang');

      // Publish assets
      $this->publishes([
        __DIR__ . '/../resources/assets' => public_path('vendor/dropzone-enhanced'),
      ], 'dropzone-enhanced-assets');
    }
  }
}
