<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests\Database;

use Illuminate\Support\Facades\Schema;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestCase;

class PhotoMigrationTest extends TestCase
{
  public function test_the_user_association_supports_uuid_without_a_users_table_or_foreign_key(): void
  {
    Schema::drop('photos');
    Schema::drop('users');
    config()->set('dropzone.database.user_id_type', 'uuid');
    config()->set('dropzone.database.users_table', null);

    $migration = require __DIR__ . '/../../database/migrations/2025_12_17_000000_create_photos_table.php';
    $migration->up();

    $this->assertTrue(Schema::hasColumns('photos', ['user_id', 'photoable_id', 'photoable_type']));
    $this->assertSame('varchar', Schema::getColumnType('photos', 'user_id'));
  }
}
