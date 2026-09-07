<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;

class ThumbnailInvalidationTestModel extends Model
{
  use HasPhotos;

  protected $guarded = [];
}

class ThumbnailInvalidationTest extends TestCase
{
  public function test_thumbnail_is_regenerated_when_the_source_file_is_replaced(): void
  {
    Storage::fake('public');
    $model = new ThumbnailInvalidationTestModel();
    $sourcePath = 'testigos/news/1877.png';
    $thumbnailPath = 'testigos/news/thumbnails/100x75_webp/1877.webp';

    $this->putImage($sourcePath, 400, 300);
    $this->assertNotNull($model->srcFromPath($sourcePath, '100x75', 'webp', 80));
    $this->assertTrue(Storage::disk('public')->exists($thumbnailPath));

    // Back-date the thumbnail and forward-date the source so it is unambiguously stale.
    $now = time();
    touch(Storage::disk('public')->path($thumbnailPath), $now - 100);
    touch(Storage::disk('public')->path($sourcePath), $now - 50);
    clearstatcache();
    $staleMtime = Storage::disk('public')->lastModified($thumbnailPath);

    $model->srcFromPath($sourcePath, '100x75', 'webp', 80);

    $this->assertGreaterThan(
      $staleMtime,
      Storage::disk('public')->lastModified($thumbnailPath),
      'The thumbnail must be regenerated when the source file is newer than the cached thumbnail.'
    );
  }

  public function test_thumbnail_is_reused_while_the_source_file_is_unchanged(): void
  {
    Storage::fake('public');
    $model = new ThumbnailInvalidationTestModel();
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

  private function putImage(string $path, int $width, int $height): void
  {
    $image = UploadedFile::fake()->image(basename($path), $width, $height);
    Storage::disk('public')->put($path, file_get_contents($image->getPathname()));
  }
}
