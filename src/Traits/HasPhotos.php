<?php

namespace MacCesar\LaravelDropzoneEnhanced\Traits;

use MacCesar\LaravelDropzoneEnhanced\Models\Photo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
   * Get the main photo for the model.
   *
   * @return \MacCesar\LaravelDropzoneEnhanced\Models\Photo|null
   */
  public function mainPhoto()
  {
    return $this->photos->where('is_main', true)->first() ?? $this->photos->first();
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
    // First, unset any existing main photo
    $this->photos()->update(['is_main' => false]);

    // Then, set the new main photo
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
}
