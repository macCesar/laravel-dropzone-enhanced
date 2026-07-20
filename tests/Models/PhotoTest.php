<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests\Models;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestCase;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestModel;
use MacCesar\LaravelDropzoneEnhanced\Tests\User;

class PhotoTest extends TestCase
{
  public function test_deleting_a_photo_clears_custom_thumbnail_files_and_cache_keys(): void
  {
    Storage::fake('public');
    $user = User::create(['name' => 'Owner']);
    $model = TestModel::create(['user_id' => $user->id]);
    $image = UploadedFile::fake()->image('photo.jpg', 400, 300);
    Storage::disk('public')->put('images/1/photo.jpg', file_get_contents($image->getPathname()));

    $photo = Photo::create([
      'photoable_id' => $model->id,
      'photoable_type' => $model->getMorphClass(),
      'user_id' => $user->id,
      'filename' => 'photo.jpg',
      'original_filename' => 'photo.jpg',
      'disk' => 'public',
      'directory' => 'images/1',
      'extension' => 'jpg',
      'mime_type' => 'image/jpeg',
      'size' => 100,
      'width' => 400,
      'height' => 300,
      'sort_order' => 1,
      'is_main' => true,
    ]);

    $this->assertNotNull($photo->getThumbnailUrl('137x91', 'webp', 77, 'top'));
    $cacheKey = "photo_thumb_{$photo->id}_137x91_webp_77_top";
    $this->assertTrue(Cache::has($cacheKey));

    $this->assertTrue($photo->deletePhoto());
    $this->assertFalse(Cache::has($cacheKey));
    $this->assertSame([], Storage::disk('public')->allFiles());
  }

  public function test_deleting_a_photo_without_url_cache_still_removes_files(): void
  {
    config()->set('dropzone.images.thumbnails.cache_urls', false);
    $photo = $this->createPhotoWithImage(400, 300);

    $this->assertNotNull($photo->getThumbnailUrl('137x91', 'webp', 77, 'top'));
    $this->assertNotSame([], Storage::disk('public')->allFiles());

    $this->assertTrue($photo->deletePhoto());
    $this->assertSame([], Storage::disk('public')->allFiles());
  }

  public function test_srcset_uses_requested_aspect_ratio_when_height_is_explicit(): void
  {
    $photo = $this->createPhotoWithImage(2476, 2066);

    $srcset = $photo->srcset('640x360');

    $this->assertStringContainsString('/640x360/', $srcset);
    $this->assertStringContainsString('/1280x720/', $srcset);
    $this->assertStringNotContainsString('1280x1068', $srcset);
  }

  public function test_srcset_width_only_keeps_original_aspect_ratio(): void
  {
    $photo = $this->createPhotoWithImage(2476, 2066);

    $srcset = $photo->srcset('640');

    $this->assertStringContainsString('/640x534/', $srcset);
    $this->assertStringContainsString('/1280x1068/', $srcset);
  }

  public function test_thumbnail_url_clamps_upscale_to_original_size(): void
  {
    $photo = $this->createPhotoWithImage(640, 534);

    $url = $photo->getThumbnailUrl('1920x1080', 'webp');

    $this->assertStringContainsString('/640x360/', $url);
    foreach (Storage::disk('public')->allFiles() as $file) {
      $this->assertStringNotContainsString('1920x1080', $file);
    }
  }

  public function test_srcset_omits_duplicate_entries_after_clamp(): void
  {
    $photo = $this->createPhotoWithImage(640, 534);

    $srcset = $photo->srcset('640x360');

    $this->assertStringContainsString('/640x360/', $srcset);
    $this->assertStringContainsString(' 1x', $srcset);
    $this->assertStringNotContainsString(' 2x', $srcset);
    $this->assertStringNotContainsString(',', $srcset);
  }

  public function test_allow_upscale_config_restores_previous_behavior(): void
  {
    config()->set('dropzone.images.thumbnails.allow_upscale', true);
    $photo = $this->createPhotoWithImage(640, 534);

    $url = $photo->getThumbnailUrl('1280x720', 'webp');

    $this->assertStringContainsString('/1280x720/', $url);
  }

  public function test_cache_urls_disabled_returns_same_url_without_cache_keys(): void
  {
    $photo = $this->createPhotoWithImage(400, 300);

    $cachedUrl = $photo->getThumbnailUrl('137x91', 'webp', 77, 'top');
    Cache::flush();

    config()->set('dropzone.images.thumbnails.cache_urls', false);
    $url = $photo->getThumbnailUrl('137x91', 'webp', 77, 'top');

    $this->assertSame($cachedUrl, $url);
    $this->assertFalse(Cache::has("photo_thumb_{$photo->id}_137x91_webp_77_top"));
    $this->assertFalse(Cache::has("photo_thumb_keys_{$photo->id}"));
  }

  private function createPhotoWithImage(int $width, int $height): Photo
  {
    Storage::fake('public');
    $user = User::create(['name' => 'Owner']);
    $model = TestModel::create(['user_id' => $user->id]);
    $image = UploadedFile::fake()->image('photo.jpg', $width, $height);
    Storage::disk('public')->put('images/1/photo.jpg', file_get_contents($image->getPathname()));

    return Photo::create([
      'photoable_id' => $model->id,
      'photoable_type' => $model->getMorphClass(),
      'user_id' => $user->id,
      'filename' => 'photo.jpg',
      'original_filename' => 'photo.jpg',
      'disk' => 'public',
      'directory' => 'images/1',
      'extension' => 'jpg',
      'mime_type' => 'image/jpeg',
      'size' => 100,
      'width' => $width,
      'height' => $height,
      'sort_order' => 1,
      'is_main' => true,
    ]);
  }
}
