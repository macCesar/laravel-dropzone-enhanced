<?php

namespace MacCesar\LaravelDropzoneEnhanced\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
    try {
      $request->validate([
        'directory' => 'required|string',
        'model_id' => 'required|integer',
        'model_type' => 'required|string',
        'dimensions' => 'nullable|string',
        'file' => 'required|file|image|max:' . config('dropzone.images.max_filesize', 5000),
      ]);

      // Get upload parameters
      $file = $request->file('file');
      $modelId = $request->input('model_id');
      $directory = $request->input('directory');
      $modelType = $request->input('model_type');
      $disk = config('dropzone.storage.disk', 'public');
      $dimensions = $request->input('dimensions', config('dropzone.images.default_dimensions'));

      // Generate a unique filename
      $extension = $file->getClientOriginalExtension();
      $filename = Str::uuid() . '.' . $extension;
      $fullPath = $directory . '/' . $filename;

      // Upload file
      $file->storeAs($directory, $filename, $disk);

      // Generate thumbnails if enabled in the configuration
      if (config('dropzone.images.thumbnails.enabled')) {
        $directory = dirname($fullPath);
        $filename = basename($fullPath);
        $thumbnailDimensions = config('dropzone.images.thumbnails.dimensions', '288x288');

        // Generate thumbnail directory
        $thumbnailDir = $directory . '/thumbnails/' . $thumbnailDimensions;
        Storage::disk($disk)->makeDirectory($thumbnailDir);

        // Generate thumbnail path
        $thumbnailPath = $thumbnailDir . '/' . $filename;

        // Process with Glide if available
        if (class_exists('MacCesar\LaravelGlideEnhanced\Facades\Glide')) {
          [$thumbWidth, $thumbHeight] = explode('x', $thumbnailDimensions);

          \MacCesar\LaravelGlideEnhanced\Facades\Glide::load($fullPath, $disk)
            ->modify([
              'fit' => 'crop',
              'w' => $thumbWidth,
              'h' => $thumbHeight,
              'q' => config('dropzone.images.quality', 90),
            ])
            ->save($thumbnailPath);
        }
      }

      // Get image dimensions and size
      $imageSize = getimagesize($file->getRealPath());
      $width = $imageSize[0];
      $height = $imageSize[1];
      $size = $file->getSize();

      // Set user_id from authenticated user if available
      $userId = auth()->check() ? auth()->id() : null;

      // Create photo record
      $photo = Photo::create([
        'disk' => $disk,
        'size' => $size,
        'width' => $width,
        'height' => $height,
        'user_id' => $userId,
        'filename' => $filename,
        'extension' => $extension,
        'directory' => $directory,
        'photoable_id' => $modelId,
        'photoable_type' => $modelType,
        'mime_type' => $file->getMimeType(),
        'original_filename' => $file->getClientOriginalName(),
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
    } catch (\Exception $e) {
      // Log the complete error for debugging
      \Log::error('Error uploading image: ' . $e->getMessage());
      \Log::error('Received data: ' . json_encode($request->all()));

      // Return error response with details
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => $request->all()
      ], 422);
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

    // Get the related model
    $modelClass = $photo->photoable_type;
    $model = $modelClass::findOrFail($photo->photoable_id);

    // Verify ownership or permission to delete this photo
    if (!$this->userCanDeletePhoto($request, $photo, $model)) {
      $message = auth()->check()
        ? 'Unauthorized. You are logged in but do not have permission to delete this photo. You may need specific ownership or permissions.'
        : 'Unauthorized. Authentication required or valid session token needed to delete this photo.';

      return response()->json([
        'success' => false,
        'message' => $message,
        'details' => [
          'authenticated' => auth()->check(),
          'model_type' => get_class($model),
          'model_id' => $model->id,
          'photo_id' => $photo->id
        ]
      ], 403);
    }

    // Delete the photo and related files
    $success = $photo->deletePhoto();

    return response()->json([
      'success' => $success,
    ]);
  }

  /**
   * Check if the current user is authorized to delete the photo.
   * This method can be extended in your application for custom authorization logic.
   *
   * @param \Illuminate\Http\Request $request
   * @param \MacCesar\LaravelDropzoneEnhanced\Models\Photo $photo
   * @param mixed $model
   * @return bool
   */
  protected function userCanDeletePhoto(Request $request, Photo $photo, $model)
  {
    // For non-authenticated scenarios, check session tokens or custom headers
    if (!auth()->check()) {
      // Check both model ID and photo ID in session tokens for better flexibility
      $sessionKey1 = "photo_access_" . get_class($model) . "_{$model->id}";
      $sessionKey2 = "photo_access_" . get_class($model) . "_{$photo->id}";

      if ($request->session()->has($sessionKey1) || $request->session()->has($sessionKey2)) {
        return true;
      }

      // Allow API or JavaScript requests with a valid access key
      if ($request->header('X-Access-Key') && $request->header('X-Access-Key') === config('dropzone.security.access_key', null)) {
        return true;
      }

      // If the photo doesn't have a user_id, anyone can delete it
      if (is_null($photo->user_id)) {
        return true;
      }

      return false;
    }

    // If the photo doesn't have a user_id, allow any authenticated user to delete it
    if (is_null($photo->user_id)) {
      return true;
    }

    // Check if the photo belongs to the authenticated user
    if ($photo->user_id === auth()->id()) {
      return true;
    }

    // Case 1: Direct model ownership via user_id field
    if (isset($model->user_id) && $model->user_id === auth()->id()) {
      return true;
    }

    // Case 2: User relationship on the model
    if (method_exists($model, 'user') && $model->user && $model->user->id === auth()->id()) {
      return true;
    }

    // Case 3: Custom ownership method on the model
    if (method_exists($model, 'isOwnedBy') && $model->isOwnedBy(auth()->user())) {
      return true;
    }

    // Case 4: User has admin status (common pattern in many apps)
    if (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()) {
      return true;
    }

    // Case 5: Laravel Gates integration
    if (method_exists(auth(), 'can') && auth()->can('delete-photos')) {
      return true;
    }

    // Case 6: Spatie Permission integration
    if (method_exists(auth()->user(), 'hasPermissionTo') && auth()->user()->hasPermissionTo('delete photos')) {
      return true;
    }

    // Optional config setting to allow all authenticated users (disabled by default)
    if (config('dropzone.security.allow_all_authenticated_users', false)) {
      return true;
    }

    return false; // Default deny if no authorization check passes
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
    $isMain = $photo->is_main;

    // If the photo is already the main, toggle it
    if ($isMain) {
      $photo->update(['is_main' => false]);

      return response()->json([
        'success' => true,
        'is_main' => false
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
      'is_main' => true
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

    try {
      foreach ($request->photos as $item) {
        Photo::where('id', $item['id'])->update(['sort_order' => $item['order']]);
      }

      return response()->json([
        'success' => true,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
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
