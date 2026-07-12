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
}
