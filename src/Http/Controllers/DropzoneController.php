<?php

namespace MacCesar\LaravelDropzoneEnhanced\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;
use MacCesar\LaravelDropzoneEnhanced\Services\ImageProcessor;

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

      // Apply EXIF orientation correction to original image
      if (in_array($file->getMimeType(), ['image/jpeg', 'image/jpg']) && function_exists('exif_read_data')) {
        $originalPath = Storage::disk($disk)->path($fullPath);
        ImageProcessor::correctOriginalImageInPlace($originalPath, $file->getMimeType());
      }

      // Generate thumbnails if enabled in the configuration
      if (config('dropzone.images.thumbnails.enabled')) {
        $directory = dirname($fullPath);
        $filename = basename($fullPath);
        $thumbnailDimensions = config('dropzone.images.thumbnails.dimensions', '288x288');

        // Generate thumbnail directory
        $thumbnailDir = $directory . '/thumbnails/' . $thumbnailDimensions;
        Storage::disk($disk)->makeDirectory($thumbnailDir);

        // Generate thumbnail using native GD
        $thumbnailPath = $thumbnailDir . '/' . $filename;
        [$thumbWidth, $thumbHeight] = explode('x', $thumbnailDimensions);

        $thumbnailGenerated = ImageProcessor::generateThumbnail(
          $fullPath,
          $thumbnailPath,
          (int) $thumbWidth,
          (int) $thumbHeight,
          $disk,
          config('dropzone.images.quality', 90)
        );

        if (!$thumbnailGenerated) {
          \Log::warning('Failed to generate thumbnail for: ' . $fullPath);
        }
      }

      // Get image dimensions and size
      $imageSize = getimagesize($file->getRealPath());
      $width = $imageSize[0];
      $height = $imageSize[1];
      $size = $file->getSize();

      // Prepare photo data
      $photoData = [
        'disk' => $disk,
        'size' => $size,
        'width' => $width,
        'height' => $height,
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
      ];

      // Only add user_id if the column exists and auth is available (full compatibility)
      if (Schema::hasColumn('photos', 'user_id')) {
        try {
          $userId = auth()->check() ? auth()->id() : null;
          $photoData['user_id'] = $userId;
        } catch (\Exception $e) {
          // If auth fails (no guards, public site, etc.), just ignore user_id
          // This ensures the package works in ANY environment
        }
      }

      // Create photo record
      $photo = Photo::create($photoData);

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
    // For public sites or sites without authentication, allow deletion by default
    try {
      $isAuthenticated = auth()->check();
    } catch (\Exception $e) {
      // If auth system is not configured, allow deletion (public site)
      return true;
    }

    // For non-authenticated scenarios, check session tokens or custom headers
    if (!$isAuthenticated) {
      // Check both model ID and photo ID in session tokens for better flexibility
      $sessionKey1 = "photo_access_" . get_class($model) . "_{$model->id}";
      $sessionKey2 = "photo_access_photo_{$photo->id}";

      if ($request->session()->has($sessionKey1) || $request->session()->has($sessionKey2)) {
        return true;
      }

      // Allow API or JavaScript requests with a valid access key
      if ($request->header('X-Access-Key') && $request->header('X-Access-Key') === config('dropzone.security.access_key', null)) {
        return true;
      }

      // For public sites, allow deletion by default (backward compatibility)
      return true;
    }

    // From here, user is authenticated - check user_id column exists
    if (!Schema::hasColumn('photos', 'user_id')) {
      // If no user_id column, allow authenticated users to delete
      return true;
    }

    // If the photo doesn't have a user_id, allow any authenticated user to delete it
    $photoUserId = $photo->user_id ?? null;
    if (is_null($photoUserId)) {
      return true;
    }

    // Check if the photo belongs to the authenticated user
    if ($photoUserId === auth()->id()) {
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
}
