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
   * Get the URL for the photo.
   *
   * @return string
   */
  public function getUrl()
  {
    // Use relative URL to avoid domain mismatches between localhost and local domains
    return '/storage/' . $this->getPath();
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
   * @return string|null
   */
  public function getThumbnailUrl($dimensions = null)
  {
    if (!config('dropzone.images.thumbnails.enabled')) {
      return $this->getUrl();
    }

    $dimensions = $dimensions ?? config('dropzone.images.thumbnails.dimensions');
    $thumbnailPath = $this->directory . '/thumbnails/' . $dimensions . '/' . $this->filename;

    // Use relative URL for thumbnails as well
    if (Storage::disk($this->disk)->exists($thumbnailPath)) {
      return '/storage/' . $thumbnailPath;
    }

    // If Glide is available, generate thumbnail on the fly
    if (class_exists('MacCesar\LaravelGlideEnhanced\Facades\Glide')) {
      return route('dropzone.image', [
        'path' => $this->getPath(),
        'w' => explode('x', $dimensions)[0],
        'h' => explode('x', $dimensions)[1],
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
    // Delete the original file
    Storage::disk($this->disk)->delete($this->getPath());

    // Delete thumbnails if they exist
    if (config('dropzone.images.thumbnails.enabled')) {
      $thumbnailDirectory = $this->directory . '/thumbnails';

      // Check if the thumbnail directory exists
      if (Storage::disk($this->disk)->exists($thumbnailDirectory)) {
        // Get all dimension directories
        $dimensionDirs = Storage::disk($this->disk)->directories($thumbnailDirectory);

        // Delete the thumbnail in each dimension directory
        foreach ($dimensionDirs as $dimDir) {
          Storage::disk($this->disk)->delete($dimDir . '/' . $this->filename);
        }
      }
    }

    // Delete the model
    return $this->delete();
  }
}
