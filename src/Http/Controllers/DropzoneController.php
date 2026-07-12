<?php

namespace MacCesar\LaravelDropzoneEnhanced\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;
use MacCesar\LaravelDropzoneEnhanced\Services\ImageProcessor;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DropzoneController extends Controller
{
  /**
   * Upload a photo.
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function upload(Request $request)
  {
    $validated = $request->validate([
      'model_type' => ['required', 'string', 'max:255'],
      'model_id' => ['required', 'integer'],
      'directory' => ['required', 'string', 'max:255', 'regex:/^(?![\\/])(?!.*(?:^|[\\/])\.\.?(?:[\\/]|$))[A-Za-z0-9._\-\\/]+$/'],
      'locale' => ['nullable', 'string', 'max:10'],
      'keep_original_name' => ['nullable', 'boolean'],
      'file' => [
        'required',
        'file',
        'max:' . config('dropzone.images.max_filesize', 5000),
      ],
    ]);

    $model = $this->resolveSignedModel($validated['model_type'], (int) $validated['model_id']);
    $this->authorizeAction('upload', $model);

    $directory = trim($validated['directory'], '/');
    $locale = config('dropzone.multilingual.enabled') ? ($validated['locale'] ?? null) : null;
    $this->ensurePhotoLimitNotExceeded($model, $locale);

    $file = $request->file('file');
    $disk = config('dropzone.storage.disk', 'public');
    $mimeType = (string) $file->getMimeType();
    $extension = $this->extensionForMimeType($mimeType);
    $keepOriginalName = $request->boolean('keep_original_name', false);
    $filename = $keepOriginalName
      ? $this->generateUniqueFilename(
        $directory,
        Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file',
        $extension,
        $disk
      )
      : Str::uuid() . '.' . $extension;
    $fullPath = $directory . '/' . $filename;
    $createdPaths = [];
    $photo = null;

    try {
      $storedPath = $file->storeAs($directory, $filename, $disk);
      if ($storedPath === false) {
        throw new \RuntimeException('The uploaded image could not be stored.');
      }
      $createdPaths[] = $fullPath;

      $imageSize = getimagesize(Storage::disk($disk)->path($fullPath));
      if ($imageSize === false) {
        throw new \RuntimeException('The stored image could not be inspected.');
      }
      $this->assertImageDimensionsAllowed((int) $imageSize[0], (int) $imageSize[1]);

      // Apply EXIF orientation correction to original image
      if ($mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
        $originalPath = Storage::disk($disk)->path($fullPath);
        ImageProcessor::correctOriginalImageInPlace($originalPath, $mimeType);
        $imageSize = getimagesize($originalPath) ?: $imageSize;
      }

      // Generate thumbnails if enabled in the configuration
      if (config('dropzone.images.thumbnails.enabled')) {
        $directory = dirname($fullPath);
        $filename = basename($fullPath);
        $thumbnailDimensions = config('dropzone.images.thumbnails.dimensions', '288x288');
        $thumbnailCrop = config('dropzone.images.thumbnails.crop_position', 'center');

        // Generate thumbnail directory
        $thumbnailDir = $directory . '/thumbnails/' . $thumbnailDimensions;
        Storage::disk($disk)->makeDirectory($thumbnailDir);

        // Generate thumbnail using native GD
        $thumbnailPath = $thumbnailDir . '/' . $filename;
        [$thumbWidth, $thumbHeight] = $this->validatedDimensions($thumbnailDimensions);

        $thumbnailGenerated = ImageProcessor::generateThumbnail(
          $fullPath,
          $thumbnailPath,
          (int) $thumbWidth,
          (int) $thumbHeight,
          $disk,
          config('dropzone.images.quality', 90),
          null,
          $thumbnailCrop
        );

        if (!$thumbnailGenerated) {
          Log::warning('Dropzone thumbnail generation failed.', ['path' => $fullPath]);
        } else {
          $createdPaths[] = $thumbnailPath;
        }
      }

      // Get image dimensions and size
      $width = $imageSize[0];
      $height = $imageSize[1];
      $size = Storage::disk($disk)->size($fullPath);

      // Prepare photo data
      $photoData = [
        'disk' => $disk,
        'size' => $size,
        'width' => $width,
        'height' => $height,
        'filename' => $filename,
        'extension' => $extension,
        'directory' => $directory,
        'photoable_id' => $model->getKey(),
        'photoable_type' => $model->getMorphClass(),
        'mime_type' => $mimeType,
        'original_filename' => $file->getClientOriginalName(),
      ];

      // Add locale if multilingual support is enabled and locale is provided
      if (config('dropzone.multilingual.enabled')) {
        $photoData['locale'] = $locale;
      }

      // Keep compatibility with installations created before user tracking.
      if (Schema::hasColumn('photos', 'user_id')) {
        $photoData['user_id'] = $request->user()?->getAuthIdentifier();
      }

      $photo = DB::transaction(function () use ($photoData, $model, $locale) {
        $query = Photo::where('photoable_id', $model->getKey())
          ->where('photoable_type', $model->getMorphClass())
          ->when(
            config('dropzone.multilingual.enabled'),
            fn ($builder) => $locale === null ? $builder->whereNull('locale') : $builder->where('locale', $locale)
          )
          ->lockForUpdate();

        abort_if(
          (clone $query)->count() >= (int) config('dropzone.images.max_files', 10),
          422,
          'The maximum number of photos has been reached.'
        );
        $photoData['sort_order'] = (clone $query)->max('sort_order') + 1;
        $photoData['is_main'] = !(clone $query)->exists();

        return Photo::create($photoData);
      });

      $this->warmConfiguredThumbnails($photo);

      return response()->json([
        'success' => true,
        'photo' => $photo,
        'url' => $photo->getUrl(),
        'thumbnail' => $photo->getThumbnailUrl(),
      ]);
    } catch (\Throwable $exception) {
      if ($photo instanceof Photo && $photo->exists) {
        $photo->deletePhoto();
      }
      Storage::disk($disk)->delete($createdPaths);

      if ($exception instanceof HttpExceptionInterface) {
        throw $exception;
      }

      $errorId = (string) Str::uuid();
      Log::error('Dropzone upload failed.', [
        'error_id' => $errorId,
        'user_id' => $request->user()?->getAuthIdentifier(),
        'model_type' => $validated['model_type'],
        'model_id' => $validated['model_id'],
        'exception' => $exception,
      ]);

      return response()->json([
        'success' => false,
        'message' => 'The image could not be uploaded.',
        'error_id' => $errorId,
      ], 500);
    }
  }

  /**
   * Delete a photo.
   *
   * @param \Illuminate\Http\Request $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function destroy(Request $request, $id)
  {
    $photo = Photo::findOrFail($id);
    $model = $photo->photoable()->firstOrFail();
    $this->authorizeAction('delete', $model, $photo);

    // Delete the photo and related files
    $success = $photo->deletePhoto();

    return response()->json([
      'success' => $success,
    ]);
  }

  /**
   * Set a photo as the main photo.
   *
   * @param \Illuminate\Http\Request $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function setMain(Request $request, $id)
  {
    $photo = Photo::findOrFail($id);
    $model = $photo->photoable()->firstOrFail();
    $this->authorizeAction('set-main', $model, $photo);

    $response = DB::transaction(function () use ($photo) {
      $group = Photo::where('photoable_id', $photo->photoable_id)
        ->where('photoable_type', $photo->photoable_type)
        ->when(
          config('dropzone.multilingual.enabled'),
          fn ($query) => $photo->locale === null ? $query->whereNull('locale') : $query->where('locale', $photo->locale)
        )
        ->lockForUpdate();

      if ($photo->is_main) {
        $photo->update(['is_main' => false]);
        $firstPhoto = (clone $group)
          ->where('id', '!=', $photo->id)
          ->orderBy('sort_order')
          ->first();
        $firstPhoto?->update(['is_main' => true]);

        return ['is_main' => false, 'new_main_id' => $firstPhoto?->id];
      }

      $group->update(['is_main' => false]);
      $photo->update(['is_main' => true]);

      return ['is_main' => true];
    });

    return response()->json(['success' => true] + $response);
  }

  /**
   * Check if a photo is the main photo.
   *
   * @param \Illuminate\Http\Request $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function checkIsMain(Request $request, $id)
  {
    $photo = Photo::findOrFail($id);
    $model = $photo->photoable()->firstOrFail();
    $this->authorizeAction('view-main-status', $model, $photo);

    return response()->json([
      'is_main' => (bool) $photo->is_main,
    ]);
  }

  /**
   * Reorder photos.
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function reorder(Request $request)
  {
    $validated = $request->validate([
      'photos' => ['required', 'array', 'min:1'],
      'photos.*.id' => ['required', 'integer', 'distinct', 'exists:photos,id'],
      'photos.*.order' => ['required', 'integer', 'min:1', 'distinct'],
    ]);

    $photos = Photo::whereKey(collect($validated['photos'])->pluck('id'))->get()->keyBy('id');
    $firstPhoto = $photos->first();
    abort_if($firstPhoto === null, 404);
    $model = $firstPhoto->photoable()->firstOrFail();
    $this->authorizeAction('reorder', $model, $firstPhoto);

    $invalidGroup = $photos->contains(fn (Photo $photo) =>
      $photo->photoable_id != $firstPhoto->photoable_id ||
      $photo->photoable_type !== $firstPhoto->photoable_type ||
      (config('dropzone.multilingual.enabled') && $photo->locale !== $firstPhoto->locale)
    );
    abort_if($invalidGroup, 422, 'All photos must belong to the same model and locale.');

    DB::transaction(function () use ($validated, $photos) {
      foreach ($validated['photos'] as $item) {
        $photos->get($item['id'])->update(['sort_order' => $item['order']]);
      }
    });

    return response()->json(['success' => true]);
  }

  /**
   * Update the locale for a photo (drag between locale groups).
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function updateLocale(Request $request)
  {
    $validated = $request->validate([
      'photo_id' => 'required|integer|exists:photos,id',
      'locale' => 'nullable|string|max:10',
    ]);

    abort_unless(config('dropzone.multilingual.enabled'), 422, 'Multilingual photo management is disabled.');
    $photo = Photo::findOrFail($validated['photo_id']);
    $model = $photo->photoable()->firstOrFail();
    $this->authorizeAction('update-locale', $model, $photo);
    $oldLocale = $photo->locale;
    DB::transaction(function () use ($photo, $oldLocale, $validated) {
      $wasMain = $photo->is_main;
      $photo->locale = $validated['locale'];
      $photo->is_main = false;
      $photo->save();

      $this->recalculateSortOrder($photo->photoable_type, $photo->photoable_id, $oldLocale);
      $this->recalculateSortOrder($photo->photoable_type, $photo->photoable_id, $validated['locale']);

      if ($wasMain) {
        Photo::where('photoable_type', $photo->photoable_type)
          ->where('photoable_id', $photo->photoable_id)
          ->when($oldLocale === null, fn ($query) => $query->whereNull('locale'), fn ($query) => $query->where('locale', $oldLocale))
          ->orderBy('sort_order')
          ->first()?->update(['is_main' => true]);
      }

      $destination = Photo::where('photoable_type', $photo->photoable_type)
        ->where('photoable_id', $photo->photoable_id)
        ->when(
          $validated['locale'] === null,
          fn ($query) => $query->whereNull('locale'),
          fn ($query) => $query->where('locale', $validated['locale'])
        );

      if ($wasMain) {
        (clone $destination)->update(['is_main' => false]);
        $photo->update(['is_main' => true]);
      } elseif (!(clone $destination)->where('is_main', true)->exists()) {
        $photo->update(['is_main' => true]);
      }
    });

    return response()->json([
      'success' => true,
      'photo' => $photo->fresh(),
      'old_locale' => $oldLocale,
      'new_locale' => $validated['locale'],
    ]);
  }

  /**
   * Recalculate sort order for photos in a locale group.
   *
   * @param string $type
   * @param int $id
   * @param string|null $locale
   * @return void
   */
  protected function recalculateSortOrder(string $type, int $id, ?string $locale): void
  {
    $photos = Photo::where('photoable_type', $type)
      ->where('photoable_id', $id)
      ->when($locale === null, function ($query) {
        return $query->whereNull('locale');
      }, function ($query) use ($locale) {
        return $query->where('locale', $locale);
      })
      ->orderBy('sort_order')
      ->get();

    foreach ($photos as $index => $photo) {
      $photo->sort_order = $index + 1;
      $photo->save();
    }
  }

  /**
   * Resolve a configured model alias and fail closed for unknown model types.
   *
   * @return \Illuminate\Database\Eloquent\Model
   */
  protected function resolveSignedModel(string $modelClass, int $id)
  {
    abort_unless(is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class), 404);

    return $modelClass::query()->findOrFail($id);
  }

  /**
   * Authorize a photo operation through the application's configured Gate.
   */
  protected function authorizeAction(string $action, $model, ?Photo $photo = null): void
  {
    if ($action === 'upload' && config('dropzone.security.allow_public_uploads', false)) {
      return;
    }

    $ability = config('dropzone.security.authorization_ability', 'dropzone.manage-photos');
    Gate::authorize($ability, [$action, $model, $photo]);
  }

  /**
   * Enforce the configured number of photos on the server.
   */
  protected function ensurePhotoLimitNotExceeded($model, ?string $locale): void
  {
    $query = Photo::where('photoable_type', $model->getMorphClass())
      ->where('photoable_id', $model->getKey())
      ->when(
        config('dropzone.multilingual.enabled'),
        fn ($builder) => $locale === null ? $builder->whereNull('locale') : $builder->where('locale', $locale)
      );

    abort_if($query->count() >= (int) config('dropzone.images.max_files', 10), 422, 'The maximum number of photos has been reached.');
  }

  /**
   * Convert a server-detected MIME type to a safe public file extension.
   */
  protected function extensionForMimeType(string $mimeType): string
  {
    return match ($mimeType) {
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/gif' => 'gif',
      'image/webp' => 'webp',
      default => throw ValidationException::withMessages([
        'file' => ['The file must be a JPEG, PNG, GIF, or WebP image.'],
      ]),
    };
  }

  /**
   * Validate image dimensions before GD decodes the image.
   */
  protected function assertImageDimensionsAllowed(int $width, int $height): void
  {
    $maxWidth = (int) config('dropzone.security.max_width', 12000);
    $maxHeight = (int) config('dropzone.security.max_height', 12000);
    $maxPixels = (int) config('dropzone.security.max_pixels', 40000000);

    abort_if(
      $width < 1 || $height < 1 || $width > $maxWidth || $height > $maxHeight || ($width * $height) > $maxPixels,
      422,
      'The image dimensions are too large.'
    );
  }

  /**
   * Parse and validate an exact thumbnail dimension.
   *
   * @return array{0:int,1:int}
   */
  protected function validatedDimensions(string $dimensions): array
  {
    abort_unless(preg_match('/^(\d+)x(\d+)$/', $dimensions, $matches) === 1, 500, 'Invalid thumbnail configuration.');
    $width = (int) $matches[1];
    $height = (int) $matches[2];
    $this->assertImageDimensionsAllowed($width, $height);

    return [$width, $height];
  }

  /**
   * Generate only the thumbnail variants configured by the server.
   */
  protected function warmConfiguredThumbnails(Photo $photo): void
  {
    $sizes = config('dropzone.images.warm_sizes', []);
    $factor = max(1, min(5, (int) config('dropzone.images.warm_factor', 1)));
    $format = config('dropzone.images.warm_format', 'webp');
    $allowedFormats = ['webp', 'jpg', 'png'];

    if (!is_array($sizes) || count($sizes) > (int) config('dropzone.security.max_warm_sizes', 10) || !in_array($format, $allowedFormats, true)) {
      throw new \UnexpectedValueException('Invalid warm thumbnail configuration.');
    }

    foreach ($sizes as $dimensions) {
      $dimensions = (string) $dimensions;
      if (ctype_digit($dimensions)) {
        $baseWidth = (int) $dimensions;
        $baseHeight = $photo->width > 0
          ? (int) round($photo->height * ($baseWidth / $photo->width))
          : 0;
      } else {
        [$baseWidth, $baseHeight] = $this->validatedDimensions($dimensions);
      }

      for ($multiplier = 1; $multiplier <= $factor; $multiplier++) {
        $width = $baseWidth * $multiplier;
        $height = $baseHeight * $multiplier;
        $this->assertImageDimensionsAllowed($width, $height);
        $photo->generateThumbnail("{$width}x{$height}", $format);
      }
    }
  }

  /**
   * Generate a unique filename within a directory (adds numeric suffix if needed).
   *
   * @param string $directory
   * @param string $baseName
   * @param string $extension
   * @param string $disk
   * @return string
   */
  protected function generateUniqueFilename(string $directory, string $baseName, string $extension, string $disk): string
  {
    $storage = Storage::disk($disk);
    $candidate = $baseName . '.' . $extension;
    $counter = 1;

    while ($storage->exists($directory . '/' . $candidate)) {
      $candidate = $baseName . '-' . $counter . '.' . $extension;
      $counter++;

      // Prevent infinite loops; fall back to UUID if too many collisions
      if ($counter > 1000) {
        return Str::uuid() . '.' . $extension;
      }
    }

    return $candidate;
  }
}
