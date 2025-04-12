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
      return request()->getSchemeAndHttpHost() . '/storage/' . $this->getPath();
    }

    // Si LaravelGlideEnhanced está disponible, usarlo (solo cuando no estamos en el caso anterior)
    if (class_exists('MacCesar\LaravelGlideEnhanced\Facades\ImageProcessor')) {
      return \MacCesar\LaravelGlideEnhanced\Facades\ImageProcessor::url($this->getPath(), ['fm' => 'keep']);
    }

    // Fallback a Storage
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
        return request()->getSchemeAndHttpHost() . '/storage/' . $thumbnailPath;
      }

      // Si no existe thumbnail y tampoco GlideEnhanced, usar la imagen original
      if (!class_exists('MacCesar\LaravelGlideEnhanced\Facades\ImageProcessor')) {
        return $this->getUrl();
      }

      // Usar ImageProcessor con dominio corregido manualmente
      [$thumbWidth, $thumbHeight] = explode('x', $dimensions);
      $url = \MacCesar\LaravelGlideEnhanced\Facades\ImageProcessor::url($this->getPath(), [
        'w' => $thumbWidth,
        'h' => $thumbHeight,
        'fit' => 'crop',
        'q' => config('dropzone.images.quality', 90),
      ]);

      // Reemplazar manualmente localhost con el host actual
      return str_replace(config('app.url'), request()->getSchemeAndHttpHost(), $url);
    }

    // Flujo normal (no estamos en localhost o accedemos desde localhost)

    // If the thumbnail exists, return its URL
    if (Storage::disk($this->disk)->exists($thumbnailPath)) {
      return Storage::disk($this->disk)->url($thumbnailPath);
    }

    // Check if we have Laravel Glide Enhanced installed
    if (class_exists('MacCesar\LaravelGlideEnhanced\Facades\ImageProcessor')) {
      // Parse dimensions to get width and height
      [$thumbWidth, $thumbHeight] = explode('x', $dimensions);

      // Use the URL builder from Laravel Glide Enhanced
      return \MacCesar\LaravelGlideEnhanced\Facades\ImageProcessor::url($this->getPath(), [
        'w' => $thumbWidth,
        'h' => $thumbHeight,
        'fit' => 'crop',
        'q' => config('dropzone.images.quality', 90),
      ]);
    }

    // Fallback to original image
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
