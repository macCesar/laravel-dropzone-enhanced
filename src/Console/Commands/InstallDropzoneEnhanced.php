<?php

namespace MacCesar\LaravelDropzoneEnhanced\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallDropzoneEnhanced extends Command
{
  protected $signature = 'dropzone-enhanced:install';

  protected $description = 'Install and configure the Laravel Dropzone Enhanced package';

  public function handle()
  {
    $this->info('Installing Laravel Dropzone Enhanced...');

    $this->info('Publishing configuration...');
    $this->callSilent('vendor:publish', [
      '--tag' => 'dropzone-enhanced-config',
    ]);

    $this->info('Publishing migrations...');
    $this->callSilent('vendor:publish', [
      '--tag' => 'dropzone-enhanced-migrations',
    ]);

    $this->info('Publishing assets...');
    $this->callSilent('vendor:publish', [
      '--tag' => 'dropzone-enhanced-assets',
    ]);

    if (!File::exists(public_path('vendor/dropzone-enhanced'))) {
      $this->warn('Assets could not be published automatically.');
      $this->warn('Please run: php artisan vendor:publish --tag=dropzone-enhanced-assets');
    }

    $this->info('Do you want to run migrations? (yes/no)');

    if ($this->confirm('Run migrations now?')) {
      $this->info('Running migrations...');
      $this->callSilent('migrate');
    }

    $this->info('Laravel Dropzone Enhanced has been installed successfully.');
    $this->info('You can now start using the components in your views.');

    return Command::SUCCESS;
  }
}
