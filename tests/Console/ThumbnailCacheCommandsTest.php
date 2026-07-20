<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests\Console;

use Illuminate\Support\Facades\Storage;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestCase;

class ThumbnailCacheCommandsTest extends TestCase
{
  public function test_clear_thumbnails_keep_preserves_listed_dimensions(): void
  {
    Storage::fake('public');
    $storage = Storage::disk('public');
    $storage->put('.cache/images/1/640x360/photo.webp', 'a');
    $storage->put('.cache/images/1/640x360_top/photo.webp', 'a');
    $storage->put('.cache/images/1/960x540/photo.webp', 'a');
    $storage->put('.cache/images/2/1280x720/other.webp', 'a');

    $this->artisan('dropzoneenhanced:clear-thumbnails', ['--keep' => '640x360', '--force' => true])
      ->assertExitCode(0);

    $this->assertTrue($storage->exists('.cache/images/1/640x360/photo.webp'));
    $this->assertTrue($storage->exists('.cache/images/1/640x360_top/photo.webp'));
    $this->assertFalse($storage->exists('.cache/images/1/960x540/photo.webp'));
    $this->assertFalse($storage->exists('.cache/images/2/1280x720/other.webp'));
  }

  public function test_clear_thumbnails_without_keep_deletes_everything(): void
  {
    Storage::fake('public');
    $storage = Storage::disk('public');
    $storage->put('.cache/images/1/640x360/photo.webp', 'a');

    $this->artisan('dropzoneenhanced:clear-thumbnails', ['--force' => true])
      ->assertExitCode(0);

    $this->assertFalse($storage->exists('.cache'));
  }

  public function test_migrate_cache_path_moves_old_directory(): void
  {
    Storage::fake('public');
    $storage = Storage::disk('public');
    $storage->put('cache/images/1/640x360/photo.webp', 'a');

    $this->artisan('dropzoneenhanced:migrate-cache-path', ['--force' => true])
      ->assertExitCode(0);

    $this->assertTrue($storage->exists('.cache/images/1/640x360/photo.webp'));
    $this->assertFalse($storage->exists('cache/images/1/640x360/photo.webp'));
  }

  public function test_migrate_cache_path_delete_removes_old_directory(): void
  {
    Storage::fake('public');
    $storage = Storage::disk('public');
    $storage->put('cache/images/1/640x360/photo.webp', 'a');

    $this->artisan('dropzoneenhanced:migrate-cache-path', ['--delete' => true, '--force' => true])
      ->assertExitCode(0);

    $this->assertFalse($storage->exists('cache'));
    $this->assertFalse($storage->exists('.cache/images/1/640x360/photo.webp'));
  }
}
