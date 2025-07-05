<?php

namespace MacCesar\LaravelDropzoneEnhanced\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

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
   * @return string
   */
  public function getUrl()
  {
    // Si estamos en un entorno local y accediendo desde un dominio no-localhost
    if (request()->getHost() !== 'localhost' && config('app.url') === 'http://localhost') {
      // Construir manualmente la URL con el dominio correcto
      $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
      return $baseUrl . '/storage/' . $this->getPath();
    }

    // Usar Storage directamente, sin integración con otros paquetes
    return Storage::disk($this->disk)->url($this->getPath());
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
   * @param string|null $dimensions
   * @return string
   */
  public function getThumbnailUrl($dimensions = null)
  {
    // Set default dimensions from config if not provided
    if (!$dimensions) {
      $dimensions = config('dropzone.images.thumbnails.dimensions', '288x288');
    }

    // Check if thumbnails are disabled in config
    if (!config('dropzone.images.thumbnails.enabled')) {
      return $this->getUrl();
    }

    // Build thumbnail path
    $directory = dirname($this->getPath());
    $filename = basename($this->getPath());
    $thumbnailPath = $directory . '/thumbnails/' . $dimensions . '/' . $filename;

    // Si estamos en un entorno local y accediendo desde un dominio no-localhost
    if (request()->getHost() !== 'localhost' && config('app.url') === 'http://localhost') {
      // Si el thumbnail existe, construir manualmente la URL
      if (Storage::disk($this->disk)->exists($thumbnailPath)) {
        $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        return $baseUrl . '/storage/' . $thumbnailPath;
      }
      // Si no existe thumbnail, usar la imagen original
      return $this->getUrl();
    }

    // Flujo normal: If the thumbnail exists, return its URL
    if (Storage::disk($this->disk)->exists($thumbnailPath)) {
      return Storage::disk($this->disk)->url($thumbnailPath);
    }

    // Fallback to original image (no dynamic thumbnail generation)
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
