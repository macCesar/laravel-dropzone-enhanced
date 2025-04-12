<?php

namespace MacCesar\LaravelDropzoneEnhanced\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;

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
    $request->validate([
      'file' => 'required|file|image|max:' . config('dropzone.images.max_filesize', 5000),
      'model_id' => 'required|integer',
      'model_type' => 'required|string',
      'directory' => 'required|string',
      'dimensions' => 'nullable|string',
    ]);

    // Get upload parameters
    $file = $request->file('file');
    $modelId = $request->input('model_id');
    $modelType = $request->input('model_type');
    $directory = $request->input('directory');
    $dimensions = $request->input('dimensions', config('dropzone.images.default_dimensions'));
    $disk = config('dropzone.storage.disk', 'public');

    // Generate a unique filename
    $extension = $file->getClientOriginalExtension();
    $filename = Str::uuid() . '.' . $extension;
    $fullPath = $directory . '/' . $filename;

    // Upload file
    $file->storeAs($directory, $filename, $disk);

    // Process image if needed (resize, create thumbnails)
    $this->processImage($disk, $fullPath, $dimensions);

    // Get image dimensions and size
    $imageSize = getimagesize($file->getRealPath());
    $width = $imageSize[0];
    $height = $imageSize[1];
    $size = $file->getSize();

    // Create photo record
    $photo = Photo::create([
      'photoable_id' => $modelId,
      'photoable_type' => $modelType,
      'filename' => $filename,
      'original_filename' => $file->getClientOriginalName(),
      'disk' => $disk,
      'directory' => $directory,
      'extension' => $extension,
      'mime_type' => $file->getMimeType(),
      'size' => $size,
      'width' => $width,
      'height' => $height,
      'sort_order' => Photo::where('photoable_id', $modelId)
        ->where('photoable_type', $modelType)
        ->count() + 1,
      'is_main' => Photo::where('photoable_id', $modelId)
        ->where('photoable_type', $modelType)
        ->count() == 0, // First photo is main by default
    ]);

    return response()->json([
      'success' => true,
      'photo' => $photo,
      'url' => $photo->getUrl(),
      'thumbnail' => $photo->getThumbnailUrl(),
    ]);
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

    // Check if the photo belongs to the authenticated user or if the user has permission
    // This can be customized based on your app's authorization logic
    $modelClass = $photo->photoable_type;
    $model = $modelClass::findOrFail($photo->photoable_id);

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

    // Si la foto ya es la principal, desmarcamos y terminamos (toggle)
    if ($photo->is_main) {
      $photo->update(['is_main' => false]);

      return response()->json([
        'success' => true,
      ]);
    }

    // Unset any previous main photo
    Photo::where('photoable_id', $photo->photoable_id)
      ->where('photoable_type', $photo->photoable_type)
      ->update(['is_main' => false]);

    // Set this photo as main
    $photo->update(['is_main' => true]);

    return response()->json([
      'success' => true,
    ]);
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
    $request->validate([
      'photos' => 'required|array',
      'photos.*.id' => 'required|integer|exists:photos,id',
      'photos.*.order' => 'required|integer',
    ]);

    foreach ($request->photos as $item) {
      Photo::where('id', $item['id'])->update(['sort_order' => $item['order']]);
    }

    return response()->json([
      'success' => true,
    ]);
  }

  /**
   * Process an image (resize, create thumbnails).
   *
   * @param string $disk
   * @param string $path
   * @param string $dimensions
   * @return void
   */
  protected function processImage($disk, $path, $dimensions)
  {
    // Skip processing if no dimensions specified
    if (empty($dimensions)) {
      return;
    }

    // Process with Laravel Glide Enhanced if available
    if (class_exists('MacCesar\LaravelGlideEnhanced\Facades\Glide')) {
      // Resize main image
      if (config('dropzone.images.pre_resize', true)) {
        [$width, $height] = explode('x', $dimensions);

        \MacCesar\LaravelGlideEnhanced\Facades\Glide::load($path, $disk)
          ->modify([
            'w' => $width,
            'h' => $height,
            'fit' => 'max',
            'q' => config('dropzone.images.quality', 90),
          ])
          ->save();
      }

      // Create thumbnail if enabled
      if (config('dropzone.images.thumbnails.enabled', true)) {
        $thumbnailDimensions = config('dropzone.images.thumbnails.dimensions', '288x288');
        [$thumbWidth, $thumbHeight] = explode('x', $thumbnailDimensions);

        $directory = dirname($path);
        $filename = basename($path);
        $thumbnailPath = $directory . '/thumbnails/' . $thumbnailDimensions . '/' . $filename;

        // Create thumbnail directory if it doesn't exist
        Storage::disk($disk)->makeDirectory($directory . '/thumbnails/' . $thumbnailDimensions);

        \MacCesar\LaravelGlideEnhanced\Facades\Glide::load($path, $disk)
          ->modify([
            'w' => $thumbWidth,
            'h' => $thumbHeight,
            'fit' => 'crop',
            'q' => config('dropzone.images.quality', 90),
          ])
          ->save($thumbnailPath);
      }
    }
  }

  /**
   * Serve an image with Glide processing.
   *
   * @param \Illuminate\Http\Request $request
   * @param string $path
   * @return mixed
   */
  public function serveImage(Request $request, $path)
  {
    if (!class_exists('MacCesar\LaravelGlideEnhanced\Facades\Glide')) {
      abort(404, 'Glide image processor not available');
    }

    $params = $request->all();
    $disk = config('dropzone.storage.disk', 'public');

    return \MacCesar\LaravelGlideEnhanced\Facades\Glide::load($path, $disk)
      ->modify($params)
      ->response();
  }
}
