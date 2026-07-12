<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests;

use MacCesar\LaravelDropzoneEnhanced\DropzoneServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends Orchestra
{
  protected function getPackageProviders($app)
  {
    return [
      DropzoneServiceProvider::class,
    ];
  }

  protected function defineEnvironment($app)
  {
    $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    $app['config']->set('database.default', 'testing');
    $app['config']->set('database.connections.testing', [
      'driver' => 'sqlite',
      'database' => ':memory:',
      'prefix' => '',
    ]);
    $app['config']->set('auth.providers.users.model', User::class);
  }

  protected function defineDatabaseMigrations()
  {
    Schema::create('users', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->timestamps();
    });

    Schema::create('test_models', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->timestamps();
    });

    $migration = require __DIR__ . '/../database/migrations/2025_12_17_000000_create_photos_table.php';
    $migration->up();
    $localeMigration = require __DIR__ . '/../database/migrations/2025_12_18_000001_add_locale_to_photos_table.php';
    $localeMigration->up();
    $localeLengthMigration = require __DIR__ . '/../database/migrations/2026_07_11_000002_expand_photo_locale_length.php';
    $localeLengthMigration->up();
  }
}
