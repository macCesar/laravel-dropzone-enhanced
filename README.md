# Laravel Dropzone Enhanced

[![Latest Version on Packagist](https://img.shields.io/packagist/v/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![Total Downloads](https://img.shields.io/packagist/dt/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![License](https://img.shields.io/packagist/l/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)

A powerful and customizable Laravel package that enhances Dropzone.js to provide an elegant and efficient image upload and management solution for your Eloquent models.

## Features

- **Seamless Integration**: Add a complete image management UI to your models with a single trait and two Blade components.
- **Standalone & Dependency-Free**: Works out-of-the-box with no need for external libraries like Glide.
- **Automatic Thumbnail Generation**: Natively processes and creates thumbnails for fast-loading galleries.
- **Full Management UI**: Includes drag & drop reordering, main image selection, lightbox preview, and secure deletion.
- **Highly Customizable**: Configure everything from image dimensions and quality to storage disks and route middleware.
- **Smart URL Generation**: Automatic relative URL generation that works consistently across all environments (local, staging, production) without `.env` configuration hassles.
- **Handy Helpers**: `src`/`srcset` helpers on models and photos (including raw storage paths) for quick, optimized URLs.
- **Broad Compatibility**: Supports Laravel 8, 9, 10, and 11.

## Requirements

- PHP 7.4 or higher
- Laravel 8.0 or higher
- **ext-exif** (for automatic image orientation correction)
- ext-gd (for image processing)

## Installation

**1. Install via Composer**
```bash
composer require maccesar/laravel-dropzone-enhanced
```

**2. Run the Installer**
This command publishes the config file, migrations, and assets.
```bash
php artisan dropzoneenhanced:install
```
Note: The legacy alias `dropzone-enhanced:install` still works.

**3. Run Migrations**
```bash
php artisan migrate
```

**4. Link Storage**
Ensure your public storage disk is linked so images are accessible.
```bash
php artisan storage:link
```

## EXIF Orientation Support

The package automatically corrects image orientation based on EXIF data from mobile photos:

- **Auto-detection**: Reads EXIF orientation data from uploaded images
- **Smart correction**: Applies rotation/flipping to both original and thumbnails
- **Fallback handling**: Gracefully handles images without EXIF data
- **Performance optimized**: Only processes JPEG images with orientation data

### Requirements for EXIF Support
- PHP `ext-exif` extension enabled
- JPEG images with EXIF metadata

Images will display correctly oriented regardless of how they were captured on mobile devices.

---

## URL Generation

### Relative vs Absolute URLs

The package can optionally use **relative URLs** (e.g., `/storage/images/photo.jpg`) instead of absolute URLs (e.g., `http://localhost:8000/storage/images/photo.jpg`). This provides several benefits:

- ✅ **Environment agnostic**: Works seamlessly across local, staging, and production without configuration changes
- ✅ **No APP_URL conflicts**: You can keep `APP_URL` in your `.env` file without it affecting image URLs
- ✅ **Better performance**: Relative URLs are lighter and load faster
- ✅ **CDN friendly**: Easier to integrate with CDNs and reverse proxies

### Configuration

Control URL generation behavior in `config/dropzone.php`:

```php
'images' => [
    // Use relative URLs (e.g., /storage/...) instead of absolute URLs (e.g., http://localhost:8000/storage/...)
    // This prevents issues with APP_URL in .env and makes URLs work across different environments
    'use_relative_urls' => true, // Default: false (disabled for backward compatibility)
],
```

**Note**: This feature is disabled by default to maintain backward compatibility with existing installations.

### Enabling Relative URLs

To enable relative URLs (recommended for most use cases):

**Step 1: Publish or update your config**
```bash
php artisan vendor:publish --tag=dropzoneenhanced-config --force
```

**Step 2: Enable the feature in config/dropzone.php**
```php
'images' => [
    'use_relative_urls' => true,
],
```

**Step 3: Clear config cache**
```bash
php artisan config:clear
```

**Option 2: Generate absolute URLs on-demand**
```php
// For a specific photo
$relativeUrl = $photo->getUrl(); // /storage/images/photo.jpg
$absoluteUrl = url($photo->getUrl()); // http://yourdomain.com/storage/images/photo.jpg

// In Blade templates
<img src="{{ url($photo->getUrl()) }}" alt="Photo">
```

### Migration from Previous Versions

If you're upgrading from v2.1.7 or earlier:

- **No action required**: Existing installations continue working with absolute URLs (default behavior)
- **Optional but recommended**: Enable relative URLs for better portability:
  1. Republish the config: `php artisan vendor:publish --tag=dropzoneenhanced-config --force`
  2. Set `'use_relative_urls' => true` in `config/dropzone.php`
  3. Clear config cache: `php artisan config:clear`
- **If you had workarounds**: Once relative URLs are enabled, you can remove any workarounds like commenting out `APP_URL` in `.env`

---

## Quickstart: A Practical Example

This guide shows the most common use case: managing photos for an existing model in an edit form.

### Step 1: Prepare Your Model

Add the `HasPhotos` trait to any Eloquent model you want to associate with images.

```php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;

class Product extends Model
{
  use HasPhotos;

  // ... your other model properties
}
```

### Step 2: Implement the View

In your Blade view (e.g., `resources/views/products/edit.blade.php`), add the two components. They work together to provide the full experience.

```blade
{{-- resources/views/products/edit.blade.php --}}

@extends('layouts.app')

@section('content')
  <h1>Edit Product: {{ $product->name }}</h1>

  <form action="{{ route('products.update', $product) }}" method="POST">
    @csrf
    @method('PUT')

    {{-- Your other form fields --}}
    <div>
      <label for="name">Product Name</label>
      <input id="name" name="name" type="text" value="{{ $product->name }}">
    </div>

    <hr>

    {{-- 1. UPLOAD NEW PHOTOS --}}
    <h3>Add New Photos</h3>
    <x-dropzone-enhanced::area :max-files="10" :max-filesize="5" :model="$product" directory="products" />

    <hr>

    {{-- 2. MANAGE EXISTING PHOTOS --}}
    <h3>Manage Existing Photos</h3>
    <p>Drag to reorder, click the star to set the main photo, or use the trash icon to delete.</p>
    <x-dropzone-enhanced::photos :lightbox="true" :model="$product" />

    <button type="submit">Save Changes</button>
  </form>
@endsection
```

### How It Works

-   The `<x-dropzone-enhanced::area />` component provides the Dropzone interface to upload new images, which are automatically associated with the same `$product`.
-   The `<x-dropzone-enhanced::photos />` component displays the gallery of already uploaded images for the given `$product`, enabling management actions (reorder, delete, set main).

---

## Component Reference

### Uploader: `<x-dropzone-enhanced::area />`

This component provides the file upload interface.

| Parameter          | Type     | Description                                                                                         | Default                                        |
| :----------------- | :------- | :-------------------------------------------------------------------------------------------------- | :--------------------------------------------- |
| `:model`           | `Model`  | **Required.** The Eloquent model instance to attach photos to.                                      |                                                |
| `directory`        | `string` | **Required.** The subdirectory within your storage disk to save the images.                         |                                                |
| `dimensions`       | `string` | Max dimensions for resize (e.g., "1920x1080").                                                      | `config('dropzone.images.default_dimensions')` |
| `preResize`        | `bool`   | Whether to resize the image in the browser before upload. Set `false` to preserve original quality. | `config('dropzone.images.pre_resize')`         |
| `maxFiles`         | `int`    | Maximum number of files allowed to be uploaded.                                                     | `config('dropzone.images.max_files')`          |
| `maxFilesize`      | `int`    | Maximum file size in MB.                                                                            | `config('dropzone.images.max_filesize')`       |
| `reloadOnSuccess`  | `bool`   | If `true`, the page will automatically reload after all uploads are successfully completed.         | `false`                                        |
| `keepOriginalName` | `bool`   | If `true`, store files using the sanitized original filename (adds numeric suffix on collisions).   | `false`                                        |

Example: keep original filenames in a custom directory
```blade
<x-dropzone-enhanced::area
  :model="$product"
  directory="uploaded-files"
  :keepOriginalName="true"
/>
```

### Gallery: `<x-dropzone-enhanced::photos />`

This component displays and manages existing photos for a model.

| Parameter   | Type    | Description                                                                 | Default |
| :---------- | :------ | :-------------------------------------------------------------------------- | :------ |
| `:model`    | `Model` | **Required.** The Eloquent model instance whose photos you want to display. |         |
| `:lightbox` | `bool`  | Enables or disables the lightbox preview when clicking an image.            | `true`  |

---

## Advanced Usage

### Working with the `HasPhotos` Trait

The trait adds several useful methods to your model:

```php
// Get all associated photos as a Collection (ordered by sort_order)
$product->photos;

// Get the main photo model instance
$photo = $product->mainPhoto();

// Get the URL of the main photo (original)
$url = $product->getMainPhotoUrl();

// Get the thumbnail URL of the main photo (default dimensions from config)
$thumbUrl = $product->getMainPhotoThumbnailUrl();

// Get custom processed images (NEW in v2.1)
$mainPhoto = $product->mainPhoto();
$customUrl = $mainPhoto?->getUrl('400x400'); // Square 400x400
$webpUrl = $mainPhoto?->getUrl('800x600', 'webp'); // WebP format
$qualityUrl = $mainPhoto?->getUrl('400x400', 'jpg', 85); // Custom quality

// Set a specific photo as the main one
$product->setMainPhoto($photoId);

// Check if the model has any photos
if ($product->hasPhotos()) {
  // ...
}

// Delete all photos associated with the model
$product->deleteAllPhotos();

// Quick helpers (NEW)
$product->src('300'); // Main photo, width-only; keeps aspect ratio
$product->srcset('300x200', 3, 'jpg'); // 1x/2x/3x srcset for main photo
$photo->src('400'); // Specific Photo model, width-only
$photo->srcset('400x300', 2, 'webp'); // Srcset for a Photo model
$product->srcFromPath('clients/avatar/main-photo.jpg', '300', 'webp'); // Any storage path
$product->srcsetFromPath('clients/avatar/main-photo.jpg', '300x300', 3, 'jpg'); // Srcset from storage path
```

### Image Helper Cheatsheet

These helpers work with the `HasPhotos` trait and the `Photo` model.

- **Main photo shortcuts (trait)**  
  ```php
  $model->src('300');                // width-only; keeps aspect ratio; uses mainPhoto(), fallback to first
  $model->srcset('300x200', 3);      // 1x/2x/3x with the given dimensions
  ```

- **Photo instance shortcuts**  
  ```php
  $photo->src('400');                // width-only; keeps aspect ratio from the original
  $photo->srcset('400x300', 2, 'jpg'); // srcset 1x/2x in JPG
  ```

- **Raw storage paths (no relation needed)**  
  ```php
  $model->srcFromPath('clients/avatar/main-photo.jpg', '320', 'webp');
  $model->srcsetFromPath('clients/avatar/main-photo.jpg', '320x320', 3, 'jpg');
  ```

Notes:
- If you pass width-only (`'300'`), height is inferred from the original aspect ratio; if it cannot be inferred, you get the original URL as 1x.
- Respects `dropzone.storage.disk`, `dropzone.images.thumbnails.*`, and `use_relative_urls`.
- Internally uses `mainPhoto()` and falls back to the first photo when none is marked as main.

### Advanced Customization Examples

#### Custom Upload Controller

Create a custom controller to extend the package's functionality:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;

class CustomDropzoneController extends DropzoneController
{
  public function upload(Request $request)
  {
    // Add custom validation rules
    $request->validate([
      'file' => 'required|image|mimes:jpeg,png,webp|dimensions:min_width=800,min_height=600',
      'directory' => 'required|string',
      'model_id' => 'required|integer',
      'model_type' => 'required|string',
    ]);

    // Custom processing before upload
    $file = $request->file('file');

    // Add watermark, custom processing, etc.
    $this->processImageBeforeUpload($file);

    // Call parent upload method
    return parent::upload($request);
  }

  private function processImageBeforeUpload($file)
  {
    // Your custom image processing logic here
    // Example: Add watermark, EXIF data removal, etc.
  }

  protected function userCanDeletePhoto(Request $request, Photo $photo, $model)
  {
    // Add custom authorization logic
    if ($model instanceof \App\Models\Product) {
      // Check if user owns the product's company
      if ($model->company_id !== auth()->user()->company_id) {
        return false;
      }
    }

    // Call parent method for default checks
    return parent::userCanDeletePhoto($request, $photo, $model);
  }
}
```

Then register your custom controller in your routes:

```php
// In routes/web.php
use App\Http\Controllers\CustomDropzoneController;

Route::post('dropzone/upload', [CustomDropzoneController::class, 'upload']);
Route::delete('dropzone/photos/{id}', [CustomDropzoneController::class, 'destroy']);
```

#### Multiple Upload Areas for Different Photo Types

Handle different image categories for the same model:

```blade
{{-- Main product gallery --}}
<div class="mb-8">
  <h3 class="mb-4 text-lg font-semibold">Product Gallery</h3>
  <x-dropzone-enhanced::area
    :maxFiles="10"
    :model="$product"
    :preResize="true"
    dimensions="1200x800"
    directory="products/{{ $product->id }}/gallery"
  />
  <x-dropzone-enhanced::photos
    :model="$product"
  />
</div>

{{-- Technical specifications images --}}
<div class="mb-8">
  <h3 class="mb-4 text-lg font-semibold">Technical Specifications</h3>
  <x-dropzone-enhanced::area
    :maxFiles="5"
    :model="$product"
    dimensions="1920x1080"
    directory="products/{{ $product->id }}/specs"
  />
</div>

{{-- Thumbnail/avatar images --}}
<div class="mb-8">
  <h3 class="mb-4 text-lg font-semibold">Product Thumbnails</h3>
  <x-dropzone-enhanced::area
    :maxFiles="3"
    :model="$product"
    :preResize="true"
    dimensions="400x400"
    directory="products/{{ $product->id }}/thumbs"
  />
</div>
```

#### Working with Photo Data

Access and manipulate photo metadata:

```php
// Get photo information
$photo = $product->photos->first();

echo $photo->filename;           // UUID filename
echo $photo->original_filename;  // Original upload name
echo $photo->extension;          // File extension
echo $photo->mime_type;          // MIME type
echo $photo->size;               // File size in bytes
echo $photo->width;              // Image width
echo $photo->height;             // Image height
echo $photo->sort_order;         // Display order
echo $photo->is_main;            // Boolean main status

// Get URLs
echo $photo->getUrl();                    // Original image URL
echo $photo->getThumbnailUrl();           // Default thumbnail (from config)
echo $photo->getPath();                   // Storage path

// Custom image processing (NEW in v2.1)
echo $photo->getUrl('400x400');           // Square 400x400
echo $photo->getUrl('800x600', 'webp');   // Rectangular WebP
echo $photo->getUrl('400x400', 'jpg', 85); // Custom quality
echo $photo->getUrl('300x200', 'png');    // PNG format

// Photo operations
$photo->deletePhoto();  // Delete photo and files
```

#### Custom Photo Filtering and Sorting

Add custom scopes to filter photos:

```php
// Create a custom Photo model extending the package's Photo
<?php

namespace App\Models;

use MacCesar\LaravelDropzoneEnhanced\Models\Photo as BasePhoto;

class Photo extends BasePhoto
{
  // Custom scopes
  public function scopeByDirectory($query, $directory)
  {
    return $query->where('directory', 'like', "%{$directory}%");
  }

  public function scopeMainPhotos($query)
  {
    return $query->where('is_main', true);
  }

  public function scopeLargeImages($query, $minWidth = 1000)
  {
    return $query->where('width', '>=', $minWidth);
  }

  // Custom accessors
  public function getFileSizeFormattedAttribute()
  {
    $bytes = $this->size;
    $units = ['B', 'KB', 'MB', 'GB'];

    for ($i = 0; $bytes > 1024; $i++) {
      $bytes /= 1024;
    }

    return round($bytes, 2) . ' ' . $units[$i];
  }

  public function getAspectRatioAttribute()
  {
    return $this->width / $this->height;
  }
}
```

Use in your models:

```php
// In your Product model, override the photos relationship
public function photos()
{
    return $this->morphMany(\App\Models\Photo::class, 'photoable')
                ->orderBy('sort_order', 'asc');
}

// Then use custom scopes
$mainPhotos = $product->photos()->mainPhotos()->get();
$galleryPhotos = $product->photos()->byDirectory('gallery')->get();
$largeImages = $product->photos()->largeImages(1200)->get();
```

#### Dynamic Configuration Based on User Roles

Configure dropzone behavior based on user permissions:

```blade
@php
  $user = auth()->user();
  $maxFiles = $user->isPremium() ? 20 : 5;
  $maxSize = $user->isPremium() ? 10 : 2; // MB
  $dimensions = $user->hasRole('photographer') ? '4000x3000' : '1920x1080';
  $enablePreResize = !$user->hasRole('professional');
@endphp

<x-dropzone-enhanced::area
  :dimensions="$dimensions"
  :maxFiles="$maxFiles"
  :maxFilesize="$maxSize"
  :model="$product"
  :preResize="$enablePreResize"
  directory="products/{{ $product->category }}/{{ $user->id }}"
/>
```

#### Custom Event Handling

Add JavaScript event listeners for custom behavior:

```html
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Custom success handler
    window.addEventListener('dropzone:success', function (event) {
      const detail = event.detail;
      console.log('Upload successful:', detail);

      // Custom notifications
      showToast('Image uploaded successfully!', 'success');

      // Update UI counters
      updatePhotoCounter();

      // Auto-refresh gallery if needed
      if (detail.isFirstPhoto) {
        location.reload(); // Refresh to show new main photo
      }
    });

    // Custom error handler
    window.addEventListener('dropzone:error', function (event) {
      const error = event.detail;
      console.error('Upload failed:', error);

      // Show detailed error messages
      if (error.message.includes('validation')) {
        showToast('Please check your file format and size', 'error');
      } else if (error.message.includes('storage')) {
        showToast('Storage error. Please try again.', 'error');
      } else {
        showToast('Upload failed: ' + error.message, 'error');
      }
    });

    // Custom progress handler
    window.addEventListener('dropzone:progress', function (event) {
      const progress = event.detail.progress;
      updateProgressBar(progress);

      // Show/hide loading overlay
      if (progress === 100) {
        hideLoadingOverlay();
      } else {
        showLoadingOverlay();
      }
    });
  });

  function showToast(message, type) {
    // Your notification system integration
  }

  function updatePhotoCounter() {
    // Update photo count in UI
    const count = document.querySelectorAll('.photo-item').length;
    document.querySelector('#photo-count').textContent = count;
  }

  function updateProgressBar(progress) {
    const progressBar = document.querySelector('#upload-progress');
    if (progressBar) {
      progressBar.style.width = progress + '%';
    }
  }
</script>
```

#### Custom CSS Styling

Override default styles with custom CSS:

```css
/* Custom dropzone styling */
.dropzone-container .dropzone {
  border: 2px dashed #4f46e5;
  border-radius: 12px;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  transition: all 0.3s ease;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dropzone:hover {
  border-color: #3730a3;
  background: linear-gradient(135deg, #eef2ff 0%, #ddd6fe 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(79, 70, 229, 0.15);
}

.dropzone.dz-drag-hover {
  border-color: #1e40af;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  transform: scale(1.02);
}

/* Custom photo gallery */
.photos-container .photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.photos-container .photo-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  cursor: move;
}

.photos-container .photo-item:hover {
  transform: scale(1.05);
  box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.2);
}

.photos-container .photo-item.is-main {
  border: 3px solid #fbbf24;
  transform: scale(1.05);
}

.photos-container .photo-item.is-main::before {
  content: "★";
  position: absolute;
  top: 8px;
  left: 8px;
  background: #fbbf24;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  z-index: 10;
}
```

## Breaking Changes in v2.1

### Enhanced Image Processing API

**BEFORE (v2.0 and earlier):**
```php
// This worked but was confusing
$product->getMainPhotoThumbnailUrl('400x400', 'webp', 85);
```

**AFTER (v2.1+):**
```php
// Simplified - thumbnails use config defaults only
$product->getMainPhotoThumbnailUrl(); // Default dimensions from config

// Enhanced - getUrl() now handles all custom processing
$mainPhoto = $product->mainPhoto();
$customUrl = $mainPhoto?->getUrl('400x400', 'webp', 85);
```

### Benefits of the New API:
- ✅ **More intuitive**: `getUrl()` for all image processing
- ✅ **Cleaner separation**: `getThumbnailUrl()` for defaults only
- ✅ **More flexible**: Support for WebP, PNG, custom quality
- ✅ **Better performance**: Dynamic generation only when needed

### Migration Guide:
```php
// Replace this:
$url = $product->getMainPhotoThumbnailUrl('400x400', 'webp');

// With this:
$mainPhoto = $product->mainPhoto();
$url = $mainPhoto?->getUrl('400x400', 'webp');
```

### Configuration

For deep customization, publish the configuration file:
```bash
php artisan vendor:publish --tag=dropzoneenhanced-config
# Alias supported: --tag=dropzone-enhanced-config
```

#### Aliases and Backward Compatibility
- Installer command: preferred `php artisan dropzoneenhanced:install`; alias `php artisan dropzone-enhanced:install`.
- Publish tags (both work):
  - Config: `dropzoneenhanced-config` (alias: `dropzone-enhanced-config`)
  - Migrations: `dropzoneenhanced-migrations` (alias: `dropzone-enhanced-migrations`)
  - Views: `dropzoneenhanced-views` (alias: `dropzone-enhanced-views`)
  - Lang: `dropzoneenhanced-lang` (alias: `dropzone-enhanced-lang`)
  - Assets: `dropzoneenhanced-assets` (alias: `dropzone-enhanced-assets`)
You can now edit `config/dropzone.php` to change default image sizes, storage disks, route middleware, and more.

### Security & Authorization

The package includes a comprehensive and robust authorization system for photo deletion to prevent unauthorized actions. It performs a series of checks for authenticated users (model ownership, `isAdmin` methods, Gates) and provides secure options for unauthenticated scenarios (session tokens, access keys).

For full details on customizing authorization logic, please refer to the extensive comments in the `config/dropzone.php` file and the source code of the `DropzoneController`.

## Security Best Practices

### File Type Validation

Always validate file types both on the client and server side:

```php
// Server-side validation (automatically handled by the package)
// The DropzoneController validates with: 'file' => 'required|file|image|max:' . config('dropzone.images.max_filesize')

// For custom validation, extend the controller:
class CustomDropzoneController extends DropzoneController
{
  public function upload(Request $request)
  {
    $request->validate([
      'directory' => 'required|string',
      'model_id' => 'required|integer',
      'model_type' => 'required|string',
      'file' => 'required|image|mimes:jpeg,png,webp|max:5120', // 5MB max
    ]);

    return parent::upload($request);
  }
}
```

### Directory Structure Security

Organize uploads in a secure directory structure to prevent unauthorized access:

```blade
{{-- Good: Organized by model type --}}
<x-dropzone-enhanced::area
  :model="$product"
  directory="products"
/>

{{-- Better: Include model ID for isolation --}}
<x-dropzone-enhanced::area
  :model="$product"
  directory="products/{{ $product->id }}"
/>

{{-- Best: Include user context for multi-tenant apps --}}
<x-dropzone-enhanced::area
  :model="$product"
  directory="users/{{ auth()->id() }}/products/{{ $product->id }}"
/>
```

### User Authorization

The package provides multiple authorization layers. The `userCanDeletePhoto()` method checks:

1. **Photo ownership**: `$photo->user_id === auth()->id()`
2. **Model ownership**: `$model->user_id === auth()->id()`
3. **User relationship**: `$model->user() && $model->user->id === auth()->id()`
4. **Custom ownership**: `$model->isOwnedBy(auth()->user())`
5. **Admin check**: `auth()->user()->isAdmin()`
6. **Laravel Gates**: `auth()->can('delete-photos')`
7. **Spatie Permissions**: `auth()->user()->hasPermissionTo('delete photos')`

To customize authorization, extend the controller:

```php
class CustomDropzoneController extends DropzoneController
{
  protected function userCanDeletePhoto(Request $request, Photo $photo, $model)
  {
    // Add your custom authorization logic
    if ($model instanceof Product && $model->company_id !== auth()->user()->company_id) {
      return false;
    }

    // Call parent method for default checks
    return parent::userCanDeletePhoto($request, $photo, $model);
  }
}
```

### Configuration Security

Review your security settings in `config/dropzone.php`:

```php
'security' => [
  // IMPORTANT: Keep this false in production
  'allow_all_authenticated_users' => false,

  // Set a strong access key for API requests
  'access_key' => env('DROPZONE_ACCESS_KEY', null),
],

'images' => [
  // Limit file sizes to prevent abuse
  'max_filesize' => 10000, // 10MB in KB
  'max_files' => 10,

  // Resize large images to save storage
  'default_dimensions' => '1920x1080',
  'pre_resize' => true,
],
```

### Database Security

The package uses polymorphic relationships with user tracking:

```php
// The photos table includes security fields:
// - user_id: Who uploaded the photo
// - photoable_id/photoable_type: What model it belongs to

// Check photo ownership programmatically:
$photo = Photo::find($photoId);
if ($photo->user_id !== auth()->id()) {
  abort(403, 'Unauthorized');
}

// Check model ownership:
$model = $photo->photoable;
if (!$model->isOwnedBy(auth()->user())) {
  abort(403, 'Unauthorized');
}
```

### File Size and Rate Limiting

Implement proper limits to prevent abuse:

```blade
<x-dropzone-enhanced::area
  :model="$product"
  :maxFiles="10"           {{-- Limit number of files --}}
  :maxFilesize="5"         {{-- Limit file size (MB) --}}
  :preResize="true"        {{-- Resize before upload --}}
  dimensions="1920x1080"   {{-- Resize large images --}}
  directory="products"
/>
```

Add rate limiting middleware to your routes:

```php
// In routes/web.php or your RouteServiceProvider
Route::middleware(['throttle:uploads'])->group(function () {
  // Dropzone routes are automatically registered
});

// In app/Http/Kernel.php
protected $middlewareGroups = [
  'web' => [
    // ... other middleware
    'throttle:60,1', // 60 requests per minute
  ],
];
```

## Performance Optimization

### Image Optimization

Configure automatic image optimization to reduce file sizes and improve loading times:

```blade
{{-- Enable pre-resize for better performance --}}
<x-dropzone-enhanced::area
  :model="$product"
  :preResize="true"             {{-- Resize in browser before upload (default) --}}
  dimensions="1200x800"         {{-- Resize to reasonable dimensions --}}
  directory="products"
/>

{{-- Disable pre-resize to preserve original image quality --}}
<x-dropzone-enhanced::area
  :model="$product"
  :preResize="false"            {{-- Upload original images without processing --}}
  directory="products"          {{-- Note: Files will be larger, uploads slower --}}
/>
```

Configure quality settings in `config/dropzone.php`:

```php
'images' => [
  'quality' => 100,                     // JPEG quality (1-100) - Default: 100 for maximum quality
  'pre_resize' => true,                 // Client-side resize - Set false to preserve original images
  'max_filesize' => 10000,              // 10MB max in KB
  'default_dimensions' => '1920x1080',  // Max dimensions

  'thumbnails' => [
    'enabled' => true,
    'dimensions' => '288x288',        // Thumbnail size
  ],
],
```

### Thumbnail Generation

The package uses the `ImageProcessor` service to generate thumbnails efficiently:

```blade
{{-- Use different thumbnail sizes for different contexts --}}
<x-dropzone-enhanced::photos
  :model="$product"
  thumbnailDimensions="200x200"  {{-- Smaller for product lists --}}
/>

<x-dropzone-enhanced::photos
  :model="$product"
  thumbnailDimensions="400x300"  {{-- Larger for detail views --}}
/>
```

Check thumbnail configuration:

```php
// Get thumbnail URL with custom dimensions
$photo = $product->photos->first();
$thumbUrl = $photo->getThumbnailUrl('300x200');

// Default thumbnail from config
$defaultThumb = $photo->getThumbnailUrl(); // Uses config('dropzone.images.thumbnails.dimensions')
```

### Database Performance

Optimize queries when working with photos:

```php
// Eager load photos to avoid N+1 queries
$products = Product::with('photos')->get();

// Get only main photos
$products = Product::with(['photos' => function($query) {
  $query->where('is_main', true);
}])->get();

// Order photos by sort_order (already done by HasPhotos trait)
$photos = $product->photos; // Automatically ordered by sort_order ASC

// Paginate photos for models with many images
$photos = $product->photos()->paginate(20);
```

### Storage Optimization

Optimize storage usage and access patterns:

```php
// Use appropriate storage disk for your needs
'storage' => [
  'disk' => 'public',        // For local development
  // 'disk' => 's3',         // For production with CDN
  'directory' => 'images',
],

// Organize files in date-based directories to avoid too many files per folder
<x-dropzone-enhanced::area
  :model="$product"
  directory="products/{{ date('Y/m') }}/{{ $product->id }}"
/>
```

### Memory Management

The `ImageProcessor` properly manages memory when generating thumbnails:

```php
// The service automatically:
// 1. Creates image resources
// 2. Generates thumbnails with proper aspect ratio
// 3. Cleans up memory with imagedestroy()
// 4. Handles different image formats (JPEG, PNG, GIF, WebP)

// For very large images, ensure adequate PHP memory:
ini_set('memory_limit', '256M');
```

### Lazy Loading

Implement lazy loading for better page performance:

```blade
{{-- The photos component includes lazy loading by default --}}
<img
  class="photo-thumb"
  src="{{ $photo->getThumbnailUrl() }}"
  alt="{{ $photo->original_filename }}"
  loading="lazy"                 {{-- Native lazy loading --}}
/>
```

### Caching Strategies

Implement caching for frequently accessed data:

```php
// Cache photo counts
public function getPhotoCountAttribute()
{
  return Cache::remember(
    "product_{$this->id}_photo_count",
    3600, // 1 hour
    fn() => $this->photos()->count()
  );
}

// Cache main photo URL
public function getCachedMainPhotoUrl()
{
  return Cache::remember(
    "product_{$this->id}_main_photo_url",
    3600,
    fn() => $this->getMainPhotoUrl()
  );
}
```

### CDN Integration

For production environments, consider using a CDN:

```php
// Override the Photo model's getUrl() method for CDN
class Photo extends \MacCesar\LaravelDropzoneEnhanced\Models\Photo
{
  public function getUrl()
  {
    $cdnUrl = config('app.cdn_url');

    if ($cdnUrl) {
      return $cdnUrl . '/' . $this->getPath();
    }

    return parent::getUrl();
  }
}
```

### Batch Operations

Handle multiple photos efficiently:

```php
// Delete multiple photos efficiently
public function deleteSelectedPhotos(array $photoIds)
{
  $photos = $this->photos()->whereIn('id', $photoIds)->get();

  foreach ($photos as $photo) {
    $photo->deletePhoto(); // Handles file deletion + DB cleanup
  }
}

// Reorder multiple photos in one operation
public function reorderPhotos(array $photoData)
{
  foreach ($photoData as $item) {
    Photo::where('id', $item['id'])
        ->update(['sort_order' => $item['order']]);
  }
}

// Bulk update main photo status
public function setMainPhoto(int $photoId): bool
{
  // Unset all main photos in one query
  $this->photos()->update(['is_main' => false]);

  // Set new main photo
  return (bool) $this->photos()
    ->where('id', $photoId)
    ->update(['is_main' => true]);
}
```

## Integration with Other Packages

### With Livewire

Integrate the package with Livewire components for reactive interfaces:

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product;

class ProductGallery extends Component
{
  public Product $product;
  public $photos;
  public $photoCount = 0;

  protected $listeners = [
    'photoUploaded' => 'refreshPhotos',
    'photoDeleted' => 'refreshPhotos',
    'photoReordered' => 'refreshPhotos',
  ];

  public function mount(Product $product)
  {
    $this->product = $product;
    $this->refreshPhotos();
  }

  public function refreshPhotos()
  {
    $this->photos = $this->product->photos()->get();
    $this->photoCount = $this->photos->count();
  }

  public function deletePhoto($photoId)
  {
    $photo = $this->product->photos()->findOrFail($photoId);
    $photo->deletePhoto();
    $this->refreshPhotos();

    session()->flash('message', 'Photo deleted successfully');
  }

  public function setMainPhoto($photoId)
  {
    $this->product->setMainPhoto($photoId);
    $this->refreshPhotos();

    session()->flash('message', 'Main photo updated');
  }

  public function render()
  {
    return view('livewire.product-gallery');
  }
}
```

Livewire component view:

```blade
{{-- resources/views/livewire/product-gallery.blade.php --}}
<div>
  @if (session()->has('message'))
    <div class="alert alert-success">
      {{ session('message') }}
    </div>
  @endif

  <div class="mb-4">
    <h3>Upload New Photos ({{ $photoCount }}/{{ config('dropzone.images.max_files', 10) }})</h3>
    <x-dropzone-enhanced::area
      :model="$product"
      :reloadOnSuccess="false"
      directory="products/{{ $product->id }}"
      wire:ignore />
  </div>

  <div class="mt-6">
    <h3>Manage Photos</h3>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
      @foreach ($photos as $photo)
        <div class="group relative">
          <img alt="{{ $photo->original_filename }}" class="{{ $photo->is_main ? 'ring-4 ring-yellow-400' : '' }} h-32 w-full rounded-lg object-cover" src="{{ $photo->getThumbnailUrl('200x200') }}">

          <div class="absolute right-2 top-2 opacity-0 transition-opacity group-hover:opacity-100">
            <button class="mr-1 rounded-full bg-yellow-500 p-1 text-xs text-white" title="Set as main photo" wire:click="setMainPhoto({{ $photo->id }})">
              ★
            </button>

            <button class="rounded-full bg-red-500 p-1 text-xs text-white" title="Delete photo" wire:click="deletePhoto({{ $photo->id }})" wire:confirm="Are you sure you want to delete this photo?">
              ×
            </button>
          </div>

          @if ($photo->is_main)
            <div class="absolute bottom-2 left-2 rounded bg-yellow-500 px-2 py-1 text-xs text-white">
              Main
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</div>

<script>
  // Listen for upload success and refresh Livewire component
  window.addEventListener('dropzone:success', function(event) {
    @this.call('refreshPhotos');
  });

  window.addEventListener('dropzone:error', function(event) {
    // Handle upload errors in Livewire context
    console.error('Upload failed:', event.detail);
  });
</script>
```

### With Spatie MediaLibrary (Alternative Implementation)

If you prefer using Spatie MediaLibrary instead of the built-in Photo model:

```php
// Alternative approach using Spatie MediaLibrary
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Product extends Model implements HasMedia
{
  use InteractsWithMedia;

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('gallery')
      ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
      ->singleFile(); // For single main image

    $this->addMediaCollection('thumbnails')
      ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
  }

  public function registerMediaConversions(Media $media = null): void
  {
    $this->addMediaConversion('thumb')
      ->width(288)
      ->height(288)
      ->sharpen(10);

    $this->addMediaConversion('large')
      ->width(1920)
      ->height(1080)
      ->quality(90);
  }

  // Helper methods to work with both systems
  public function getMainPhotoUrl()
  {
    if ($this->hasPhotos()) {
      return $this->getMainPhotoUrl(); // Use package method
    }

    // Fallback to MediaLibrary
    return $this->getFirstMediaUrl('gallery', 'large');
  }
}
```

### With Laravel Sanctum API

Create API endpoints for mobile or SPA applications:

```php
// routes/api.php
use App\Http\Controllers\Api\DropzoneApiController;

Route::middleware('auth:sanctum')->group(function () {
  Route::post('photos/upload', [DropzoneApiController::class, 'upload']);
  Route::delete('photos/{photo}', [DropzoneApiController::class, 'destroy']);
  Route::post('photos/{photo}/main', [DropzoneApiController::class, 'setMain']);
  Route::post('photos/reorder', [DropzoneApiController::class, 'reorder']);
});
```

API Controller:

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;

class DropzoneApiController extends DropzoneController
{
  public function upload(Request $request)
  {
    try {
      $response = parent::upload($request);
      $data = $response->getData();

      if ($data->success) {
        return response()->json([
          'success' => true,
          'photo' => [
            'id' => $data->photo->id,
            'url' => $data->photo->getUrl(),
            'thumbnail' => $data->photo->getThumbnailUrl(),
            'filename' => $data->photo->original_filename,
            'size' => $data->photo->size,
            'is_main' => $data->photo->is_main,
          ]
        ]);
      }

      return $response;
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Upload failed',
        'error' => $e->getMessage()
      ], 422);
    }
  }

  public function destroy(Photo $photo)
  {
    try {
      // Use the package's authorization logic
      if (!$this->userCanDeletePhoto(request(), $photo, $photo->photoable)) {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized'
        ], 403);
      }

      $success = $photo->deletePhoto();

      return response()->json([
        'success' => $success,
        'message' => $success ? 'Photo deleted successfully' : 'Failed to delete photo'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Delete failed',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
```

### With Inertia.js and Vue

Use the package with Inertia.js for Vue.js applications:

```vue
<!-- resources/js/Pages/Products/Edit.vue -->
<template>
  <div>
    <h1>Edit Product: {{ product.name }}</h1>

    <!-- Upload Area -->
    <div class="mb-8">
      <h3>Upload New Photos</h3>
      <DropzoneArea :model="product" directory="products" :max-files="10" :max-filesize="5" @upload-success="handleUploadSuccess" @upload-error="handleUploadError" />
    </div>

    <!-- Photo Gallery -->
    <div class="mb-8">
      <h3>Manage Photos ({{ photos.length }})</h3>
      <PhotoGallery :photos="photos" @photo-deleted="handlePhotoDelete" @main-photo-changed="handleMainPhotoChange" @photos-reordered="handlePhotoReorder" />
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { Inertia } from '@inertiajs/inertia'
import DropzoneArea from '@/Components/DropzoneArea.vue'
import PhotoGallery from '@/Components/PhotoGallery.vue'

export default {
  components: {
    DropzoneArea,
    PhotoGallery
  },

  props: {
    product: Object,
    photos: Array
  },

  setup(props) {
    const photos = ref(props.photos)

    const handleUploadSuccess = (photo) => {
      photos.value.push(photo)
      // Show success notification
      this.$toast.success('Photo uploaded successfully')
    }

    const handleUploadError = (error) => {
      this.$toast.error('Upload failed: ' + error.message)
    }

    const handlePhotoDelete = (photoId) => {
      photos.value = photos.value.filter(photo => photo.id !== photoId)
      this.$toast.success('Photo deleted successfully')
    }

    const handleMainPhotoChange = (photoId) => {
      photos.value.forEach(photo => {
        photo.is_main = photo.id === photoId
      })
      this.$toast.success('Main photo updated')
    }

    const handlePhotoReorder = (reorderedPhotos) => {
      photos.value = reorderedPhotos
    }

    return {
      photos,
      handleUploadSuccess,
      handleUploadError,
      handlePhotoDelete,
      handleMainPhotoChange,
      handlePhotoReorder
    }
  }
}
</script>
```

### With Filament Admin Panel

Integrate with Filament for admin interfaces:

```php
// app/Filament/Resources/ProductResource.php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;

class ProductResource extends Resource
{
  public static function form(Form $form): Form
  {
    return $form->schema([
      // Other form fields...

      Section::make('Photos')
        ->schema([
          // Custom photo management component
          ViewField::make('photos')
            ->view('filament.forms.dropzone-photos')
            ->viewData(fn($record) => [
              'product' => $record,
              'photos' => $record?->photos ?? collect(),
            ]),
        ]),
    ]);
  }
}
```

Custom Filament view:

```blade
{{-- resources/views/filament/forms/dropzone-photos.blade.php --}}
<div class="space-y-4">
  @if ($product)
    <!-- Upload Area -->
    <x-dropzone-enhanced::area
      :maxFiles="10"
      :maxFilesize="5"
      :model="$product"
      directory="products/{{ $product->id }}"
    />

    <!-- Photos Gallery -->
    @if ($photos->count() > 0)
      <div class="mt-4 grid grid-cols-3 gap-4">
        @foreach ($photos as $photo)
          <div class="relative">
            <img alt="{{ $photo->original_filename }}" class="{{ $photo->is_main ? 'ring-2 ring-primary-500' : '' }} h-32 w-full rounded object-cover" src="{{ $photo->getThumbnailUrl('200x200') }}">

            @if ($photo->is_main)
              <div class="bg-primary-500 absolute left-1 top-1 rounded px-2 py-1 text-xs text-white">
                Main
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  @else
    <p class="text-gray-500">Save the product first to add photos.</p>
  @endif
</div>
```

## Troubleshooting

### Common Issues

#### Files Not Uploading

**Problem**: Files are not uploading or dropzone area is not responsive.

**Solutions:**
1. Check that your model has the `HasPhotos` trait:
   ```php
   use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;

   class Product extends Model
   {
       use HasPhotos;
   }
   ```

2. Verify the routes are correctly registered:
   ```bash
   php artisan route:list | grep dropzone
   ```
   Should show: `POST dropzone/upload`, `DELETE dropzone/photos/{id}`, etc.

3. Check browser console for JavaScript errors

4. Ensure CSRF token is present in your page (required for web middleware)

#### Permission Denied Errors

**Problem**: Files upload but return 403/permission errors.

**Solutions:**
1. Check storage directory permissions:
   ```bash
   chmod -R 775 storage/app/public/
   ```

2. Verify the storage link exists:
   ```bash
   php artisan storage:link
   ```

3. Check your `.env` file has correct `APP_URL`

4. Verify the `disk` configuration in `config/dropzone.php` matches your storage setup

#### Images Not Displaying

**Problem**: Files upload successfully but don't display in gallery.

**Solutions:**
1. Run storage link command:
   ```bash
   php artisan storage:link
   ```

2. Clear application cache:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

3. Check that `storage/app/public/` directory is writable

4. Verify your model relationship is working:
   ```php
   $product = Product::find(1);
   dd($product->photos); // Should return a collection
   ```

5. Check the `getUrl()` method is returning valid URLs:
   ```php
   $photo = $product->photos->first();
   dd($photo->getUrl()); // Should return a valid URL (relative or absolute based on config)
   ```

6. If you're seeing absolute URLs with `localhost:8000` or wrong domain:
   ```bash
   # Enable relative URLs feature to fix this issue
   # Step 1: Republish config
   php artisan vendor:publish --tag=dropzoneenhanced-config --force

   # Step 2: Edit config/dropzone.php and set 'use_relative_urls' => true

   # Step 3: Clear cache
   php artisan config:clear
   ```

> **Note**: As of v2.1.8, you can enable **relative URLs** (`/storage/...`) to ensure consistency across all environments. This feature is opt-in (disabled by default) to maintain backward compatibility. Once enabled, it prevents issues with `APP_URL` in `.env` affecting image URLs.

#### File Size Issues

**Problem**: Large files fail to upload.

**Solutions:**
1. Check PHP configuration in `php.ini`:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   max_execution_time = 300
   memory_limit = 256M
   ```

2. Update your dropzone configuration:
   ```blade
   <x-dropzone-enhanced::area
       :model="$product"
       :maxFilesize="10"
       directory="products"
   />
   ```

3. Check the `max_filesize` setting in `config/dropzone.php`

#### Thumbnail Generation Issues

**Problem**: Original images display but thumbnails don't generate.

**Solutions:**
1. Ensure GD extension is installed:
   ```bash
   php -m | grep -i gd
   ```

2. Check thumbnail configuration in `config/dropzone.php`:
   ```php
   'thumbnails' => [
       'enabled' => true,
       'dimensions' => '288x288',
   ],
   ```

3. Verify thumbnail directories are created with proper permissions

4. Check logs for thumbnail generation errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### FAQ

**Q: Can I upload files other than images?**
A: The package is designed for images, but you can modify the validation rules in the controller to accept other file types.

**Q: How do I limit the number of files per model?**
A: Use the `:maxFiles` parameter on the dropzone component:
```blade
<x-dropzone-enhanced::area :model="$product" :maxFiles="5" directory="products" />
```

**Q: Can I customize the upload directory structure?**
A: Yes, the `directory` parameter accepts nested paths:
```blade
<x-dropzone-enhanced::area :model="$product" directory="products/{{ $product->category }}" />
```

**Q: How do I handle different image sizes for different models?**
A: Use different `dimensions` parameters for each model:
```blade
<x-dropzone-enhanced::area :model="$product" dimensions="1920x1080" directory="products" />
<x-dropzone-enhanced::area :model="$user" dimensions="400x400" directory="avatars" />
```

**Q: How do I customize thumbnail dimensions?**
A: Use the `thumbnailDimensions` prop on the photos component:
```blade
<x-dropzone-enhanced::photos :model="$product" thumbnailDimensions="400x300" />
```

**Q: Can I add custom validation rules?**
A: Yes, extend the `DropzoneController` and override the `upload` method with your custom validation.

**Q: Why are my image URLs showing `http://localhost:8000` in production?**
A: Enable the relative URLs feature (available since v2.1.8) to prevent this issue:
```bash
# Step 1: Republish the config to get the new setting
php artisan vendor:publish --tag=dropzoneenhanced-config --force

# Step 2: Edit config/dropzone.php and set 'use_relative_urls' => true

# Step 3: Clear config cache
php artisan config:clear
```

**Q: How do I use absolute URLs instead of relative URLs?**
A: Absolute URLs are the default behavior. The package only uses relative URLs if you explicitly enable it by setting `'use_relative_urls' => true` in `config/dropzone.php`.

**Q: Can I mix relative and absolute URLs?**
A: Yes, you can convert on-demand:
```php
// Get relative URL (default)
$relativeUrl = $photo->getUrl(); // /storage/images/photo.jpg

// Convert to absolute when needed
$absoluteUrl = url($photo->getUrl()); // http://yourdomain.com/storage/images/photo.jpg
```

## Development & Contributing

### Asset Management

This package uses NPM to manage Dropzone.js assets. For contributors:

Asset workflow (maintainers only):
- Script: `scripts/build-assets.js` copies from `node_modules/dropzone/dist/` to `resources/assets/`.
- Files: `dropzone-min.js`, `dropzone-min.js.map`, `dropzone.css`, `dropzone.css.map`.
- Publish: `php artisan vendor:publish --tag=dropzoneenhanced-assets` (alias: `dropzone-enhanced-assets`).
- Consumers don’t need NPM; maintainers run these when updating Dropzone.

```bash
# Install dependencies
npm install

# Build assets from node_modules
npm run build-assets

# Update Dropzone.js to latest version
npm run update-dropzone
```

The package includes Dropzone.js **6.0.0-beta.2** with full source map support for debugging.

### Contributing Guidelines

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
