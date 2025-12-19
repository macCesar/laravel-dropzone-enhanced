<?php

namespace MacCesar\LaravelDropzoneEnhanced\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use MacCesar\LaravelDropzoneEnhanced\Services\ImageProcessor;

class Photo extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'photoable_id',
    'photoable_type',
    'user_id',
    'filename',
    'original_filename',
    'disk',
    'directory',
    'extension',
    'mime_type',
    'size',
    'width',
    'height',
    'sort_order',
    'is_main',
    'locale',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'is_main' => 'boolean',
    'width' => 'integer',
    'height' => 'integer',
    'size' => 'integer',
    'sort_order' => 'integer',
  ];

  /**
   * Propiedades para la documentación del IDE
   *
   * @property int $id
   * @property string $photoable_type
   * @property int $photoable_id
   * @property int $user_id
   * @property string $filename
   * @property string $original_filename
   * @property string $disk
   * @property string $directory
   * @property string $extension
   * @property string $mime_type
   * @property int $size
   * @property int $width
   * @property int $height
   * @property int $sort_order
   * @property bool $is_main
   * @property string|null $locale
   * @property \Carbon\Carbon $created_at
   * @property \Carbon\Carbon $updated_at
   */

  /**
   * Get the parent photoable model.
   */
  public function photoable()
  {
    return $this->morphTo();
  }

  /**
   * Get the user who uploaded the photo.
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function user()
  {
    return $this->belongsTo(config('auth.providers.users.model'));
  }

  /**
   * Scope a query to only include photos for a specific locale.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param string|null $locale
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeForLocale($query, $locale = null)
  {
    if ($locale === null) {
      return $query->whereNull('locale');
    }

    return $query->where('locale', $locale);
  }


  /**
   * Get all photos for a model grouped by locale.
   *
   * @param string $photoableType
   * @param int $photoableId
   * @return \Illuminate\Support\Collection
   */
  public static function groupByLocale($photoableType, $photoableId)
  {
    return static::where('photoable_type', $photoableType)
      ->where('photoable_id', $photoableId)
      ->orderBy('locale')
      ->orderBy('sort_order')
      ->get()
      ->groupBy('locale');
  }

  /**
   * Get the URL for the photo.
   *
   * @param string|null $dimensions Format: "widthxheight" (e.g., "400x400")
   * @param string|null $format Output format: 'jpg', 'png', 'webp', 'gif' (null = original)
   * @param int|null $quality Image quality 0-100 (null = config default)
   * @return string
   */
  public function getUrl($dimensions = null, $format = null, $quality = null)
  {
    // If no processing needed, return original URL
    if (!$dimensions && !$format) {
      return $this->buildUrl($this->getPath());
    }

    // For processed images, delegate to getThumbnailUrl
    return $this->getThumbnailUrl($dimensions, $format, $quality);
  }

  /**
   * Get the path for the photo.
   *
   * @return string
   */
  public function getPath()
  {
    return $this->directory . '/' . $this->filename;
  }

  /**
   * Get the thumbnail URL for the photo.
   *
   * @param string|null $dimensions Format: "widthxheight" (e.g., "400x400")
   * @param string|null $format Output format: 'jpg', 'png', 'webp', 'gif' (null = keep original)
   * @param int|null $quality Image quality 0-100 (null = use config default)
   * @return string
   */
  public function getThumbnailUrl($dimensions = null, $format = null, $quality = null, $cropPosition = 'center')
  {
    // Set default dimensions from config if not provided
    if (!$dimensions) {
      $dimensions = config('dropzone.images.thumbnails.dimensions', '288x288');
    }

    // Check if thumbnails are disabled in config
    if (!config('dropzone.images.thumbnails.enabled', true)) {
      return $this->getUrl();
    }

    // Resolve dimensions (supports width-only by keeping aspect ratio)
    [$width, $height] = $this->resolveDimensions($dimensions);
    if ($width && !$height) {
      $height = $this->inferHeightFromOriginal($width);
    }

    if (!$width || !$height) {
      return $this->getUrl();
    }

    $cropPosition = $cropPosition ?: config('dropzone.images.thumbnails.crop_position', 'center');
    $dimensionsString = $width . 'x' . $height;
    $canonicalCrop = ImageProcessor::canonicalCropPosition($cropPosition);

    // Check cache first (optimization #1: avoid repeated file system checks)
    $cacheKey = "photo_thumb_{$this->id}_{$dimensionsString}_" . ($format ?? 'orig') . "_{$quality}_{$canonicalCrop}";

    if (Cache::has($cacheKey)) {
      return Cache::get($cacheKey);
    }

    // Build thumbnail path
    $thumbnailPath = $this->buildThumbnailPath($dimensionsString, $format, $canonicalCrop);

    // Check if thumbnail already exists
    if (Storage::disk($this->disk)->exists($thumbnailPath)) {
      $url = $this->buildUrl($thumbnailPath);
      // Cache for 1 hour (optimization #2: cache successful URLs)
      Cache::put($cacheKey, $url, 3600);
      return $url;
    }

    // Generate thumbnail dynamically if it doesn't exist
    if ($this->generateThumbnail($dimensionsString, $format, $quality, $canonicalCrop)) {
      $url = $this->buildUrl($thumbnailPath);
      Cache::put($cacheKey, $url, 3600);
      return $url;
    }

    // Fallback to original image if thumbnail generation failed
    return $this->getUrl();
  }

  /**
   * Generate a thumbnail for the photo with the specified dimensions.
   *
   * @param string $dimensions Format: "widthxheight" (e.g., "400x400")
   * @param string|null $format Output format: 'jpg', 'png', 'webp', 'gif' (null = keep original)
   * @param int|null $quality Image quality 0-100 (null = use config default)
   * @return bool Success status
   */
  public function generateThumbnail($dimensions, $format = null, $quality = null, string $cropPosition = 'center')
  {
    // Basic validation (optimization #3: fail fast)
    if (!$dimensions || !preg_match('/^(\d+)x(\d+)$/', $dimensions, $matches)) {
      return false;
    }

    $width = (int) $matches[1];
    $height = (int) $matches[2];

    // Build paths
    $sourcePath = $this->getPath();
    $cropPosition = $cropPosition ?: config('dropzone.images.thumbnails.crop_position', 'center');
    $thumbnailPath = $this->buildThumbnailPath($dimensions, $format, ImageProcessor::canonicalCropPosition($cropPosition));

    // Get quality from config or parameter
    if ($quality === null) {
      $quality = config('dropzone.images.thumbnails.quality', 90);
    }

    // Generate thumbnail using ImageProcessor
    $success = ImageProcessor::generateThumbnail(
      $sourcePath,
      $thumbnailPath,
      $width,
      $height,
      $this->disk,
      $quality,
      $format,
      $cropPosition
    );

    // Clear cache on successful generation (optimization #4: invalidate cache)
    if ($success) {
      $this->clearThumbnailCache($dimensions, $format, $quality, $cropPosition);
    }

    return $success;
  }

  /**
   * Delete the photo and its thumbnails.
   *
   * @return bool
   */
  public function deletePhoto()
  {
    try {
      // Get all paths to delete (optimization #5: better thumbnail discovery)
      $paths = [$this->getPath()];
      $directory = dirname($this->getPath());
      $filename = pathinfo($this->getPath(), PATHINFO_FILENAME);
      $extension = pathinfo($this->getPath(), PATHINFO_EXTENSION);

      // Find thumbnail directories
      $thumbnailBaseDir = $directory . '/thumbnails';
      $storage = Storage::disk($this->disk);

      if ($storage->exists($thumbnailBaseDir)) {
        $subdirs = $storage->directories($thumbnailBaseDir);

        foreach ($subdirs as $subdir) {
          // Check for files with different extensions
          $possibleFiles = [
            $subdir . '/' . $filename . '.' . $extension,
            $subdir . '/' . $filename . '.jpg',
            $subdir . '/' . $filename . '.jpeg',
            $subdir . '/' . $filename . '.png',
            $subdir . '/' . $filename . '.webp',
            $subdir . '/' . $filename . '.gif',
          ];

          foreach ($possibleFiles as $file) {
            if ($storage->exists($file)) {
              $paths[] = $file;
            }
          }
        }
      }

      // Delete all files
      foreach ($paths as $path) {
        if ($storage->exists($path)) {
          $storage->delete($path);
        }
      }

      // Clear all related cache (optimization #6: cache cleanup)
      $this->clearAllCache();

      // Delete the database record
      return $this->delete();
    } catch (\Exception $e) {
      \Log::error('Photo deletion failed: ' . $e->getMessage(), [
        'photo_id' => $this->id,
        'path' => $this->getPath()
      ]);
      return false;
    }
  }

  /**
   * Build URL handling local environment (optimization #7: DRY - Don't Repeat Yourself)
   *
   * @param string $path
   * @return string
   */
  private function buildUrl($path)
  {
    // Get the URL from Laravel's Storage
    $url = Storage::disk($this->disk)->url($path);

    // Check if we should use relative URLs (from config or default behavior)
    // If config key doesn't exist, default to false to maintain backward compatibility
    // with existing installations that haven't updated their config
    $useRelativeUrls = config('dropzone.images.use_relative_urls');

    // If the config value is null (key doesn't exist), use false for backward compatibility
    if ($useRelativeUrls === null) {
      $useRelativeUrls = false;
    }

    if ($useRelativeUrls) {
      // Convert absolute or protocol-relative URLs to path-relative URLs
      // Handle both http://domain/path and //domain/path formats
      if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        // Full absolute URL: http://domain/path or https://domain/path
        $parsedUrl = parse_url($url);
        $url = ($parsedUrl['path'] ?? '') . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
      } elseif (str_starts_with($url, '//')) {
        // Protocol-relative URL: //domain/path
        // Remove everything up to and including the domain
        $url = preg_replace('#^//[^/]+#', '', $url);
      }
      // If already relative (/path), leave as is
    }

    return $url;
  }

  /**
   * Build thumbnail path (optimization #8: centralized path building)
   *
   * @param string $dimensions
   * @param string|null $format
   * @return string
   */
  private function buildThumbnailPath($dimensions, $format = null, string $cropPosition = 'center')
  {
    $directory = dirname($this->getPath());
    $filename = basename($this->getPath());
    $canonicalCrop = ImageProcessor::canonicalCropPosition($cropPosition);

    // If format is specified, change file extension
    if ($format) {
      $pathInfo = pathinfo($filename);
      $filename = $pathInfo['filename'] . '.' . $format;
    }

    $pathSuffix = $format ? "_{$format}" : '';
    $cropSuffix = $canonicalCrop !== 'center' ? '_' . str_replace('-', '_', $canonicalCrop) : '';

    return $directory . '/thumbnails/' . $dimensions . $pathSuffix . $cropSuffix . '/' . $filename;
  }

  /**
   * Clear specific thumbnail cache (optimization #9: targeted cache clearing)
   *
   * @param string $dimensions
   * @param string|null $format
   * @param int|null $quality
   */
  private function clearThumbnailCache($dimensions, $format = null, $quality = null, string $cropPosition = 'center')
  {
    $cacheKey = "photo_thumb_{$this->id}_{$dimensions}_" . ($format ?? 'orig') . "_{$quality}_" . ImageProcessor::canonicalCropPosition($cropPosition);
    Cache::forget($cacheKey);
  }

  /**
   * Clear all cache for this photo (optimization #10: bulk cache clearing)
   */
  private function clearAllCache()
  {
    // Note: This is a simple implementation. For production with Redis,
    // you might want to use cache tags or a more sophisticated approach.
    $commonQualities = [null, 80, 90, 95];
    $commonFormats = [null, 'jpg', 'png', 'webp'];
    $commonDimensions = ['288x288', '400x400', '800x600', '1200x800'];
    $cropPositions = ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'];

    foreach ($commonDimensions as $dim) {
      foreach ($commonFormats as $fmt) {
        foreach ($commonQualities as $qual) {
          foreach ($cropPositions as $cropPos) {
            $this->clearThumbnailCache($dim, $fmt, $qual, $cropPos);
          }
        }
      }
    }
  }

  /**
   * Convenience: get URL with optional resize/convert (alias for getUrl).
   *
   * @param string|null $dimensions
   * @param string|null $format
   * @param int|null $quality
   * @return string
   */
  public function src(?string $dimensions = null, ?string $format = 'webp', ?int $quality = null)
  {
    return $this->getUrl($dimensions, $format, $quality);
  }

  /**
   * Convenience: build srcset for this photo.
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
    $dimensions ??= config('dropzone.images.thumbnails.dimensions', '288x288');
    [$width, $height] = $this->resolveDimensions($dimensions);
    if ($width && !$height) {
      $height = $this->inferHeightFromOriginal($width);
    }

    if (!$width || !$height) {
      $single = $this->getUrl($dimensions, $format, $quality);
      return $single ? "{$single} 1x" : null;
    }

    $urls = [];
    for ($i = 1; $i <= $multipliers; $i++) {
      $scaledDim = ($width * $i) . 'x' . ($height * $i);
      $url = $this->getUrl($scaledDim, $format, $quality);
      if ($url) {
        $urls[] = "{$url} {$i}x";
      }
    }

    return $urls ? implode(', ', $urls) : null;
  }

  /**
   * Resolve dimensions string into width/height integers.
   *
   * @param string|null $dimensions
   * @return array{0:int|null,1:int|null}
   */
  private function resolveDimensions(?string $dimensions): array
  {
    if (!$dimensions) {
      return [null, null];
    }

    if (ctype_digit((string) $dimensions)) {
      return [(int) $dimensions, null];
    }

    if (preg_match('/^(\\d+)x(\\d+)$/', $dimensions, $matches)) {
      return [(int) $matches[1], (int) $matches[2]];
    }

    return [null, null];
  }

  /**
   * Infer height using original photo aspect ratio.
   *
   * @param int $targetWidth
   * @return int|null
   */
  private function inferHeightFromOriginal(int $targetWidth): ?int
  {
    if ($this->width && $this->height && $this->width > 0) {
      return (int) round($this->height * ($targetWidth / $this->width));
    }

    return null;
  }
}
