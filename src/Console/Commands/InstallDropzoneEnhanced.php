<?php

namespace MacCesar\LaravelDropzoneEnhanced\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class InstallDropzoneEnhanced extends Command
{
  // New canonical command name (prefix without hyphen)
  protected $signature = 'dropzoneenhanced:install {--no-interaction : Run without prompts}';

  // Backward-compatible alias for previous command name
  protected $aliases = ['dropzone-enhanced:install'];

  protected $description = 'Install and configure the Laravel Dropzone Enhanced package';

  public function handle()
  {
    $this->info('Installing Laravel Dropzone Enhanced...');

    // Check if we're updating and need migrations
    $this->checkForPendingMigrations();

    $this->info('Publishing configuration...');
    $this->callSilent('vendor:publish', [
      '--tag' => 'dropzoneenhanced-config',
    ]);

    $this->info('Publishing assets...');
    $this->callSilent('vendor:publish', [
      '--tag' => 'dropzoneenhanced-assets',
    ]);

    if (!File::exists(public_path('vendor/dropzone-enhanced'))) {
      $this->warn('Assets could not be published automatically.');
      $this->warn('Please run: php artisan vendor:publish --tag=dropzoneenhanced-assets');
    }

    // Handle migrations based on interaction mode
    if ($this->option('no-interaction')) {
      // Non-interactive mode: run migrations automatically
      $this->info('Running migrations...');
      $this->callSilent('migrate');
    } else {
      // Interactive mode: ask user
      if ($this->confirm('Run migrations now?', true)) {
        $this->info('Running migrations...');
        $this->callSilent('migrate');
      }
    }

    $this->info('Laravel Dropzone Enhanced has been installed successfully.');
    $this->info('You can now start using the components in your views.');

    return Command::SUCCESS;
  }

  protected function checkForPendingMigrations()
  {
    // Check if photos table exists but doesn't have user_id column
    if (Schema::hasTable('photos') && !Schema::hasColumn('photos', 'user_id')) {
      $this->warn('⚠️  IMPORTANT: Your photos table needs to be updated.');
      $this->warn('   A new migration is available to add user association features.');
      $this->warn('   Run "php artisan migrate" after installation to get enhanced security.');
      $this->warn('   (The package will work without it, but you\'ll miss out on new features)');
      $this->newLine();
    }
  }
}
