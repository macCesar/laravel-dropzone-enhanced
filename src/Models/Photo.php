<?php

namespace MacCesar\LaravelDropzoneEnhanced\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
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
      // Si estamos en un entorno local y accediendo desde un dominio no-localhost
      if (request()->getHost() !== 'localhost' && config('app.url') === 'http://localhost') {
        // Construir manualmente la URL con el dominio correcto
        $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        return $baseUrl . '/storage/' . $this->getPath();
      }

      // Usar Storage directamente, sin integración con otros paquetes
      return Storage::disk($this->disk)->url($this->getPath());
    }

    // For processed images, use internal processing
    return $this->processImage($dimensions, $format, $quality);
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
   * Get the thumbnail URL for the photo using default settings from config.
   * For custom processing, use getUrl() with parameters.
   *
   * @return string
   */
  public function getThumbnailUrl()
  {
    // Use default settings from config
    $dimensions = config('dropzone.images.thumbnails.dimensions', '288x288');

    // Check if thumbnails are disabled in config
    if (!config('dropzone.images.thumbnails.enabled')) {
      return $this->getUrl();
    }

    // Delegate to getUrl for processing
    return $this->getUrl($dimensions);
  }

  /**
   * Generate a thumbnail for the photo with the specified dimensions.
   *
   * @param string $dimensions Format: "widthxheight" (e.g., "400x400")
   * @param string|null $format Output format: 'jpg', 'png', 'webp', 'gif' (null = keep original)
   * @param int|null $quality Image quality 0-100 (null = use config default)
   * @return bool Success status
   */
  public function generateThumbnail($dimensions, $format = null, $quality = null)
  {
    // Parse dimensions
    if (!preg_match('/^(\d+)x(\d+)$/', $dimensions, $matches)) {
      return false;
    }

    $width = (int) $matches[1];
    $height = (int) $matches[2];

    // Build paths
    $sourcePath = $this->getPath();
    $directory = dirname($sourcePath);
    $filename = basename($sourcePath);

    // If format is specified, change file extension
    if ($format) {
      $pathInfo = pathinfo($filename);
      $filename = $pathInfo['filename'] . '.' . $format;
    }

    $pathSuffix = $format ? "_{$format}" : '';
    $thumbnailPath = $directory . '/thumbnails/' . $dimensions . $pathSuffix . '/' . $filename;

    // Get quality from config or parameter
    if ($quality === null) {
      $quality = config('dropzone.images.thumbnails.quality', 90);
    }

    // Generate thumbnail using ImageProcessor
    return ImageProcessor::generateThumbnail(
      $sourcePath,
      $thumbnailPath,
      $width,
      $height,
      $this->disk,
      $quality,
      $format
    );
  }

  /**
   * Process image with custom dimensions, format, and quality.
   * Private method used internally by getUrl().
   *
   * @param string $dimensions Format: "widthxheight" (e.g., "400x400")
   * @param string|null $format Output format: 'jpg', 'png', 'webp', 'gif' (null = keep original)
   * @param int|null $quality Image quality 0-100 (null = use config default)
   * @return string
   */
  private function processImage($dimensions, $format = null, $quality = null)
  {
    // Build thumbnail path with format consideration
    $directory = dirname($this->getPath());
    $filename = basename($this->getPath());

    // If format is specified, change file extension
    if ($format) {
      $pathInfo = pathinfo($filename);
      $filename = $pathInfo['filename'] . '.' . $format;
    }

    $pathSuffix = $format ? "_{$format}" : '';
    $thumbnailPath = $directory . '/thumbnails/' . $dimensions . $pathSuffix . '/' . $filename;

    // Check if thumbnail already exists
    if (Storage::disk($this->disk)->exists($thumbnailPath)) {
      // Si estamos en un entorno local y accediendo desde un dominio no-localhost
      if (request()->getHost() !== 'localhost' && config('app.url') === 'http://localhost') {
        $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        return $baseUrl . '/storage/' . $thumbnailPath;
      }

      return Storage::disk($this->disk)->url($thumbnailPath);
    }

    // Generate thumbnail dynamically if it doesn't exist
    $this->generateThumbnail($dimensions, $format, $quality);

    // Check again if thumbnail was created successfully
    if (Storage::disk($this->disk)->exists($thumbnailPath)) {
      // Si estamos en un entorno local y accediendo desde un dominio no-localhost
      if (request()->getHost() !== 'localhost' && config('app.url') === 'http://localhost') {
        $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        return $baseUrl . '/storage/' . $thumbnailPath;
      }

      return Storage::disk($this->disk)->url($thumbnailPath);
    }

    // Fallback to original image if thumbnail generation failed
    return $this->getUrl();
  }

  /**
   * Delete the photo and its thumbnails.
   *
   * @return bool
   */
  public function deletePhoto()
  {
    // Get all paths to delete (original and thumbnails)
    $paths = [$this->getPath()];

    // Find and add thumbnail paths if they exist
    $directory = dirname($this->getPath());
    $filename = basename($this->getPath());

    // Add default thumbnail dimension
    $defaultDimensions = config('dropzone.images.thumbnails.dimensions', '288x288');
    $paths[] = $directory . '/thumbnails/' . $defaultDimensions . '/' . $filename;

    // Delete all files from storage
    foreach ($paths as $path) {
      if (Storage::disk($this->disk)->exists($path)) {
        Storage::disk($this->disk)->delete($path);
      }
    }

    // Delete the database record
    return $this->delete();
  }
}
