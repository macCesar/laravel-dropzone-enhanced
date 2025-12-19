<?php

namespace MacCesar\LaravelDropzoneEnhanced\Traits;

use MacCesar\LaravelDropzoneEnhanced\Models\Photo;
use MacCesar\LaravelDropzoneEnhanced\Services\ImageProcessor;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

trait HasPhotos
{
  /**
   * Get all photos for the model.
   *
   * @return \Illuminate\Database\Eloquent\Relations\MorphMany
   */
  public function photos(): MorphMany
  {
    return $this->morphMany(Photo::class, 'photoable')
      ->orderBy('sort_order', 'asc');
  }

  /**
   * Get photos for a specific locale.
   *
   * @param string|null $locale
   * @return \Illuminate\Support\Collection
   */
  public function photosByLocale(?string $locale = null)
  {
    return $this->morphMany(Photo::class, 'photoable')
      ->forLocale($locale)
      ->orderBy('sort_order', 'asc')
      ->get();
  }


  /**
   * Get all photos grouped by locale.
   *
   * @return \Illuminate\Support\Collection
   */
  public function photosGroupedByLocale()
  {
    return Photo::groupByLocale(get_class($this), $this->id);
  }

  /**
   * Check if the model has photos for a specific locale.
   *
   * @param string|null $locale
   * @return bool
   */
  public function hasPhotosForLocale(?string $locale = null): bool
  {
    return $this->photosByLocale($locale)->count() > 0;
  }

  /**
   * Delete all photos for a specific locale.
   *
   * @param string|null $locale
   * @return void
   */
  public function deletePhotosForLocale(?string $locale): void
  {
    $photos = $this->photosByLocale($locale);

    foreach ($photos as $photo) {
      $photo->deletePhoto();
    }
  }

  /**
   * Get the main photo for the model.
   *
   * @param string|null $locale
   * @return \MacCesar\LaravelDropzoneEnhanced\Models\Photo|null
   */
  public function mainPhoto(?string $locale = null)
  {
    if (!config('dropzone.multilingual.enabled') || $locale === null) {
      return $this->photos->where('is_main', true)->first() ?? $this->photos->first();
    }

    $photos = $this->photosByLocale($locale);
    return $photos->where('is_main', true)->first() ?? $photos->first();
  }

  /**
   * Get the main photo URL for the model.
   *
   * @return string|null
   */
  public function getMainPhotoUrl($dimensions = null, $format = null, $quality = null)
  {
    return $this->mainPhoto()?->getUrl($dimensions, $format, $quality);
  }

  /**
   * Get the main photo thumbnail URL for the model.
   *
   * @param string|null $dimensions
   * @return string|null
   */
  public function getMainPhotoThumbnailUrl($dimensions = null)
  {
    return $this->mainPhoto()?->getThumbnailUrl($dimensions);
  }

  /**
   * Set a photo as the main photo for the model.
   *
   * @param int $photoId
   * @return bool
   */
  public function setMainPhoto(int $photoId): bool
  {
    $photo = $this->photos()->where('id', $photoId)->first();

    if (!$photo) {
      return false;
    }

    // Unset main for same locale only
    $this->photos()
      ->where('locale', $photo->locale)
      ->update(['is_main' => false]);

    // Set the new main photo
    return (bool) $this->photos()->where('id', $photoId)->update(['is_main' => true]);
  }

  /**
   * Check if the model has photos.
   *
   * @return bool
   */
  public function hasPhotos(): bool
  {
    return $this->photos()->count() > 0;
  }

  /**
   * Delete all photos associated with the model.
   *
   * @return void
   */
  public function deleteAllPhotos(): void
  {
    $photos = $this->photos;

    foreach ($photos as $photo) {
      $photo->deletePhoto();
    }
  }

  /**
   * Get an optimized image URL from a storage path (independent of the photos relation).
   *
   * @param string $path Relative path on disk (e.g., "clients/avatar/main-photo.jpg")
   * @param string|null $dimensions Format: "widthxheight" (e.g., "400x400")
   * @param string|null $format Output format: 'jpg', 'png', 'webp', 'gif' (null = keep original)
   * @param int|null $quality Image quality 0-100 (null = config default)
   * @param string|null $disk Storage disk (null = dropzone.storage.disk or default filesystem disk)
   * @return string|null
   */
  public function getPhotoUrlFromPath(
    string $path,
    ?string $dimensions = null,
    ?string $format = null,
    ?int $quality = null,
    ?string $disk = null,
    string $cropPosition = 'center'
  ): ?string {
    $disk ??= config('dropzone.storage.disk', config('filesystems.default'));
    $storage = Storage::disk($disk);

    if (!$storage->exists($path)) {
      return null;
    }

    // If only format is provided, use default thumbnail dimensions (same behavior as Photo::getThumbnailUrl)
    if (!$dimensions && $format) {
      $dimensions = config('dropzone.images.thumbnails.dimensions', '288x288');
    }

    // If no processing is requested, return the original URL
    if (!$dimensions && !$format) {
      return $this->normalizePhotoUrl($storage->url($path));
    }

    // Respect config: if thumbnails are disabled, return original
    if (!config('dropzone.images.thumbnails.enabled', true)) {
      return $this->normalizePhotoUrl($storage->url($path));
    }

    [$width, $height] = $this->parseDimensions($dimensions);

    if ($width && !$height) {
      $height = $this->inferHeightFromPath($path, $width, $disk);
    }

    // If dimensions could not be resolved, return original
    if (!$width || !$height) {
      return $this->normalizePhotoUrl($storage->url($path));
    }

    $cropPosition = $cropPosition ?: config('dropzone.images.thumbnails.crop_position', 'center');
    $dimensionsString = $width . 'x' . $height;
    $canonicalCrop = ImageProcessor::canonicalCropPosition($cropPosition);
    $thumbnailPath = $this->buildThumbnailPathFromPath($path, $dimensionsString, $format, $canonicalCrop);
    $quality ??= config('dropzone.images.thumbnails.quality', 90);

    if (
      $storage->exists($thumbnailPath) ||
      ImageProcessor::generateThumbnail(
        $path,
        $thumbnailPath,
        $width,
        $height,
        $disk,
        $quality,
        $format,
        $cropPosition
      )
    ) {
      return $this->normalizePhotoUrl($storage->url($thumbnailPath));
    }

    // Fallback to original URL if generation fails
    return $this->normalizePhotoUrl($storage->url($path));
  }

  /**
   * Build thumbnail path for a raw storage path.
   *
   * @param string $path
   * @param string $dimensions
   * @param string|null $format
   * @return string
   */
  protected function buildThumbnailPathFromPath(string $path, string $dimensions, ?string $format = null, string $cropPosition = 'center'): string
  {
    $directory = dirname($path);
    $filename = basename($path);
    $canonicalCrop = ImageProcessor::canonicalCropPosition($cropPosition);

    if ($format) {
      $pathInfo = pathinfo($filename);
      $filename = $pathInfo['filename'] . '.' . $format;
    }

    $pathSuffix = $format ? "_{$format}" : '';
    $cropSuffix = $canonicalCrop !== 'center' ? '_' . str_replace('-', '_', $canonicalCrop) : '';
    return $directory . '/thumbnails/' . $dimensions . $pathSuffix . $cropSuffix . '/' . $filename;
  }

  /**
   * Normalize URL according to configuration (relative vs absolute).
   *
   * @param string $url
   * @return string
   */
  protected function normalizePhotoUrl(string $url): string
  {
    $useRelativeUrls = config('dropzone.images.use_relative_urls');

    if ($useRelativeUrls === null) {
      $useRelativeUrls = false;
    }

    if (!$useRelativeUrls) {
      return $url;
    }

    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
      $parsedUrl = parse_url($url);
      return ($parsedUrl['path'] ?? '') . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
    }

    if (str_starts_with($url, '//')) {
      return preg_replace('#^//[^/]+#', '', $url);
    }

    return $url;
  }

  /**
   * Boot the trait.
   *
   * @return void
   */
  protected static function bootHasPhotos(): void
  {
    // When deleting the model, delete all associated photos
    static::deleting(function ($model) {
      if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
        return;
      }

      $model->deleteAllPhotos();
    });
  }

  /**
   * Convenience: get a URL for a storage path (resize/convert optional).
   *
   * @param string $path
   * @param string|null $dimensions
   * @param string|null $format
   * @param int|null $quality
   * @param string|null $disk
   * @return string|null
   */
  public function srcFromPath(
    string $path,
    ?string $dimensions = null,
    ?string $format = 'webp',
    ?int $quality = null,
    ?string $disk = null,
    string $cropPosition = 'center'
  ): ?string {
    return $this->getPhotoUrlFromPath($path, $dimensions, $format, $quality, $disk, $cropPosition);
  }

  /**
   * Convenience: build a srcset for a storage path.
   *
   * @param string $path
   * @param string|null $dimensions
   * @param int $multipliers
   * @param string|null $format
   * @param int|null $quality
   * @param string|null $disk
   * @return string|null
   */
  public function srcsetFromPath(
    string $path,
    ?string $dimensions = null,
    int $multipliers = 2,
    ?string $format = 'webp',
    ?int $quality = null,
    ?string $disk = null,
    string $cropPosition = 'center'
  ): ?string {
    $dimensions ??= config('dropzone.images.thumbnails.dimensions', '288x288');
    [$width, $height] = $this->parseDimensions($dimensions);

    if ($width && !$height) {
      $height = $this->inferHeightFromPath($path, $width, $disk ?? config('dropzone.storage.disk', config('filesystems.default')));
    }

    $urls = [];

    if (!$width || !$height) {
      $single = $this->getPhotoUrlFromPath($path, null, $format, $quality, $disk, $cropPosition);
      return $single ? "{$single} 1x" : null;
    }

    for ($i = 1; $i <= $multipliers; $i++) {
      $scaledDim = ($width * $i) . 'x' . ($height * $i);
      $url = $this->getPhotoUrlFromPath($path, $scaledDim, $format, $quality, $disk, $cropPosition);
      if ($url) {
        $urls[] = "{$url} {$i}x";
      }
    }

    return $urls ? implode(', ', $urls) : null;
  }

  /**
   * Convenience: get main photo URL with optional resize/convert.
   *
   * @param string|null $dimensions
   * @param string|null $format
   * @param int|null $quality
   * @return string|null
   */
  public function src(?string $dimensions = null, ?string $format = 'webp', ?int $quality = null): ?string
  {
    $photo = $this->mainPhoto();

    if (!$photo) {
      return null;
    }

    return $photo->getUrl($dimensions, $format, $quality);
  }

  /**
   * Convenience: build srcset for the main photo.
   *
   * @param string|null $dimensions
   * @param int $multipliers
   * @param string|null $format
   * @param int|null $quality
   * @return string|null
   */
  public function srcset(
    ?string $dimensions = null,
    int $multipliers = 2,
    ?string $format = 'webp',
    ?int $quality = null
  ): ?string {
    $photo = $this->mainPhoto();

    if (!$photo) {
      return null;
    }

    $dimensions ??= config('dropzone.images.thumbnails.dimensions', '288x288');
    [$width, $height] = $this->parseDimensions($dimensions);
    if ($width && !$height) {
      $height = $this->inferHeightFromPhoto($photo, $width);
    }
    $urls = [];

    if (!$width || !$height) {
      $single = $photo->getUrl($dimensions, $format, $quality);
      return $single ? "{$single} 1x" : null;
    }

    for ($i = 1; $i <= $multipliers; $i++) {
      $scaledDim = ($width * $i) . 'x' . ($height * $i);
      $url = $photo->getUrl($scaledDim, $format, $quality);
      if ($url) {
        $urls[] = "{$url} {$i}x";
      }
    }

    return $urls ? implode(', ', $urls) : null;
  }

  /**
   * Parse dimensions string (e.g., "800x600") into width/height integers.
   *
   * @param string|null $dimensions
   * @return array{0:int|null,1:int|null}
   */
  protected function parseDimensions(?string $dimensions): array
  {
    if (!$dimensions) {
      return [null, null];
    }

    if (ctype_digit((string) $dimensions)) {
      return [(int) $dimensions, null];
    }

    $parts = explode('x', $dimensions);
    $width = isset($parts[0]) && ctype_digit((string) $parts[0]) ? (int) $parts[0] : null;
    $height = isset($parts[1]) && ctype_digit((string) $parts[1]) ? (int) $parts[1] : null;

    return [$width, $height];
  }

  /**
   * Infer height using original photo aspect ratio.
   *
   * @param \MacCesar\LaravelDropzoneEnhanced\Models\Photo $photo
   * @param int $targetWidth
   * @return int|null
   */
  protected function inferHeightFromPhoto(Photo $photo, int $targetWidth): ?int
  {
    if ($photo->width && $photo->height && $photo->width > 0) {
      return (int) round($photo->height * ($targetWidth / $photo->width));
    }

    return null;
  }

  /**
   * Infer height from a raw storage path using original aspect ratio.
   *
   * @param string $path
   * @param int $targetWidth
   * @param string $disk
   * @return int|null
   */
  protected function inferHeightFromPath(string $path, int $targetWidth, string $disk): ?int
  {
    $storage = Storage::disk($disk);
    $fullPath = $storage->path($path);

    if (!file_exists($fullPath)) {
      return null;
    }

    $info = @getimagesize($fullPath);
    if (!$info || empty($info[0]) || empty($info[1]) || $info[0] <= 0) {
      return null;
    }

    return (int) round($info[1] * ($targetWidth / $info[0]));
  }
}
