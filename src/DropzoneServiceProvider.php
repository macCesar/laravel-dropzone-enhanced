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
      // Migrations (legacy + new tag)
      $this->publishes([
        __DIR__ . '/../database/migrations' => database_path('migrations'),
      ], 'dropzone-enhanced-migrations');
      $this->publishes([
        __DIR__ . '/../database/migrations' => database_path('migrations'),
      ], 'dropzoneenhanced-migrations');

      // Publish configuration
      // Config (legacy + new tag)
      $this->publishes([
        __DIR__ . '/../config/dropzone.php' => config_path('dropzone.php'),
      ], 'dropzone-enhanced-config');
      $this->publishes([
        __DIR__ . '/../config/dropzone.php' => config_path('dropzone.php'),
      ], 'dropzoneenhanced-config');

      // Publish views
      // Views (legacy + new tag)
      $this->publishes([
        __DIR__ . '/../resources/views' => resource_path('views/vendor/dropzone-enhanced'),
      ], 'dropzone-enhanced-views');
      $this->publishes([
        __DIR__ . '/../resources/views' => resource_path('views/vendor/dropzone-enhanced'),
      ], 'dropzoneenhanced-views');

      // Publish translations
      // Translations (legacy + new tag)
      $this->publishes([
        __DIR__ . '/../resources/lang' => resource_path('lang/vendor/dropzone-enhanced'),
      ], 'dropzone-enhanced-lang');
      $this->publishes([
        __DIR__ . '/../resources/lang' => resource_path('lang/vendor/dropzone-enhanced'),
      ], 'dropzoneenhanced-lang');

      // Publish assets
      // Assets (legacy + new tag)
      $this->publishes([
        __DIR__ . '/../resources/assets' => public_path('vendor/dropzone-enhanced'),
      ], 'dropzone-enhanced-assets');
      $this->publishes([
        __DIR__ . '/../resources/assets' => public_path('vendor/dropzone-enhanced'),
      ], 'dropzoneenhanced-assets');
    }
  }
}
