<?php

namespace MacCesar\LaravelDropzoneEnhanced\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckDropzoneUpdate extends Command
{
  protected $signature = 'dropzone-enhanced:check-update';

  protected $description = 'Check if Laravel Dropzone Enhanced requires database updates';

  public function handle()
  {
    $this->info('Checking Laravel Dropzone Enhanced database status...');
    $this->newLine();

    // Check if photos table exists
    if (!Schema::hasTable('photos')) {
      $this->error('❌ Photos table does not exist.');
      $this->warn('   Run: php artisan migrate');
      return Command::FAILURE;
    }

    $this->info('✅ Photos table exists.');

    // Check if user_id column exists
    if (!Schema::hasColumn('photos', 'user_id')) {
      $this->warn('⚠️  UPDATE AVAILABLE: user_id column is missing.');
      $this->warn('   This column adds user association and enhanced security features.');
      $this->newLine();
      $this->info('To update:');
      $this->info('   php artisan migrate');
      $this->newLine();
      $this->info('Benefits of updating:');
      $this->info('   • Associate photos with users');
      $this->info('   • Enhanced security for photo deletion');
      $this->info('   • Better audit trail');
      $this->newLine();
      $this->warn('Note: The package works without this update, but you\'ll miss new features.');

      return Command::SUCCESS;
    }

    $this->info('✅ user_id column exists.');
    $this->newLine();
    $this->info('🎉 Your Laravel Dropzone Enhanced installation is up to date!');

    return Command::SUCCESS;
  }
}
