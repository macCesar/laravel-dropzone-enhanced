<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests\Models;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestCase;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestModel;
use MacCesar\LaravelDropzoneEnhanced\Tests\User;

class ThumbnailInvalidationTest extends TestCase
{
  public function test_path_thumbnail_is_regenerated_when_the_source_file_is_replaced(): void
  {
    Storage::fake('public');
    $model = $this->makeModel();
    $sourcePath = 'testigos/news/1877.png';
    $thumbnailPath = 'testigos/news/thumbnails/100x75_webp/1877.webp';

    $this->putImage($sourcePath, 400, 300);
    $this->assertNotNull($model->srcFromPath($sourcePath, '100x75', 'webp', 80));
    $this->assertTrue(Storage::disk('public')->exists($thumbnailPath));

    $staleMtime = $this->makeThumbnailStale($sourcePath, $thumbnailPath);

    $model->srcFromPath($sourcePath, '100x75', 'webp', 80);

    $this->assertGreaterThan(
      $staleMtime,
      Storage::disk('public')->lastModified($thumbnailPath),
      'The thumbnail must be regenerated when the source file is newer than the cached thumbnail.'
    );
  }

  public function test_photo_thumbnail_is_regenerated_when_the_source_file_is_replaced(): void
  {
    config()->set('dropzone.images.thumbnails.cache_urls', false);

    $photo = $this->createPhoto();
    $sourcePath = 'images/1/photo.jpg';
    $thumbnailPath = '.cache/images/1/100x75/photo.webp';

    $this->assertNotNull($photo->getThumbnailUrl('100x75', 'webp', 80));
    $this->assertTrue(Storage::disk('public')->exists($thumbnailPath));

    $staleMtime = $this->makeThumbnailStale($sourcePath, $thumbnailPath);

    $photo->getThumbnailUrl('100x75', 'webp', 80);

    $this->assertGreaterThan(
      $staleMtime,
      Storage::disk('public')->lastModified($thumbnailPath),
      'The thumbnail must be regenerated when the source file is newer than the cached thumbnail.'
    );
  }

  public function test_photo_thumbnail_is_regenerated_even_when_the_url_cache_is_warm(): void
  {
    config()->set('dropzone.images.thumbnails.cache_urls', true);

    $photo = $this->createPhoto();
    $sourcePath = 'images/1/photo.jpg';
    $thumbnailPath = '.cache/images/1/100x75/photo.webp';

    $this->assertNotNull($photo->getThumbnailUrl('100x75', 'webp', 80));

    $staleMtime = $this->makeThumbnailStale($sourcePath, $thumbnailPath);

    $photo->getThumbnailUrl('100x75', 'webp', 80);

    $this->assertGreaterThan(
      $staleMtime,
      Storage::disk('public')->lastModified($thumbnailPath),
      'A warm URL cache must not keep serving a thumbnail whose source has changed.'
    );
  }

  public function test_thumbnail_is_reused_while_the_source_file_is_unchanged(): void
  {
    Storage::fake('public');
    $model = $this->makeModel();
    $sourcePath = 'testigos/news/1877.png';
    $thumbnailPath = 'testigos/news/thumbnails/100x75_webp/1877.webp';

    $this->putImage($sourcePath, 400, 300);
    $model->srcFromPath($sourcePath, '100x75', 'webp', 80);

    // Source untouched and older than its thumbnail: the cached file must be reused as-is.
    $mtime = Storage::disk('public')->lastModified($thumbnailPath);
    touch(Storage::disk('public')->path($sourcePath), $mtime - 5);
    clearstatcache();
    $reusedMtime = Storage::disk('public')->lastModified($thumbnailPath);

    $model->srcFromPath($sourcePath, '100x75', 'webp', 80);

    $this->assertSame(
      $reusedMtime,
      Storage::disk('public')->lastModified($thumbnailPath),
      'An up-to-date thumbnail must be served from disk, not regenerated on every request.'
    );
  }

  /**
   * Back-date the thumbnail and forward-date the source so the thumbnail is unambiguously stale.
   */
  private function makeThumbnailStale(string $sourcePath, string $thumbnailPath): int
  {
    $now = time();
    touch(Storage::disk('public')->path($thumbnailPath), $now - 100);
    touch(Storage::disk('public')->path($sourcePath), $now - 50);
    clearstatcache();

    return Storage::disk('public')->lastModified($thumbnailPath);
  }

  private function putImage(string $path, int $width, int $height): void
  {
    $image = UploadedFile::fake()->image(basename($path), $width, $height);
    Storage::disk('public')->put($path, file_get_contents($image->getPathname()));
  }

  private function makeModel(): TestModel
  {
    $user = User::create(['name' => 'Owner']);

    return TestModel::create(['user_id' => $user->id]);
  }

  private function createPhoto(): Photo
  {
    Storage::fake('public');
    $user = User::create(['name' => 'Owner']);
    $model = TestModel::create(['user_id' => $user->id]);
    $this->putImage('images/1/photo.jpg', 400, 300);

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
      'width' => 400,
      'height' => 300,
      'sort_order' => 1,
      'is_main' => true,
    ]);
  }
}
