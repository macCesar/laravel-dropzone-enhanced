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
        \MacCesar\LaravelDropzoneEnhanced\Console\Commands\ClearThumbnailsCommand::class,
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

    // Load translations (package defaults + published overrides)
    $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'dropzone-enhanced');
    $publishedLangPath = resource_path('lang/vendor/dropzone-enhanced');
    if (is_dir($publishedLangPath)) {
      $this->loadTranslationsFrom($publishedLangPath, 'dropzone-enhanced');
    }
    $legacyLangPath = function_exists('lang_path')
      ? lang_path('vendor/dropzone-enhanced')
      : null;
    if ($legacyLangPath && $legacyLangPath !== $publishedLangPath && is_dir($legacyLangPath)) {
      $this->loadTranslationsFrom($legacyLangPath, 'dropzone-enhanced');
    }

    // Load migrations automatically
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

    if ($this->app->runningInConsole()) {
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
        __DIR__ . '/../resources/lang' => $publishedLangPath,
      ], 'dropzone-enhanced-lang');
      $this->publishes([
        __DIR__ . '/../resources/lang' => $publishedLangPath,
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
