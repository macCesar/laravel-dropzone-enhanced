# Laravel Dropzone Enhanced

[![Latest Version on Packagist](https://img.shields.io/packagist/v/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![Total Downloads](https://img.shields.io/packagist/dt/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![License](https://img.shields.io/packagist/l/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)

A powerful and customizable Laravel package that enhances Dropzone.js to provide elegant and efficient image uploading, processing, and management for your Laravel 8+ applications.

## Features

- **Simple Integration**: Easily add Dropzone to any model with a simple trait
- **Static Thumbnails**: Generate and serve thumbnails stored on disk
- **Drag & Drop Reordering**: Intuitive drag-and-drop interface for sorting images
- **Main Image Selection**: Designate a main image for your models with toggle capability
- **Lightbox Preview**: View full-size images with an integrated lightbox
- **Responsive Design**: Works beautifully on any device or screen size
- **Multi-language Support**: Built-in translations for English and Spanish
- **Local Assets**: No external CDN dependencies for improved reliability
- **Customizable**: Extensively configurable through a simple config file
- **Secure**: Built with security best practices

## Requirements

- PHP 7.4 or higher
- Laravel 8.0 or higher

## Installation

### Step 1: Install the Package

```bash
composer require maccesar/laravel-dropzone-enhanced
```

### Step 2: Publish Assets and Configuration

You can use the included command to set up everything automatically:

```bash
php artisan dropzone-enhanced:install
```

Or manually publish the assets:

```bash
# Publish config file
php artisan vendor:publish --tag=dropzone-enhanced-config

# Publish migrations
php artisan vendor:publish --tag=dropzone-enhanced-migrations

# Publish assets
php artisan vendor:publish --tag=dropzone-enhanced-assets
```

### Step 3: Run the Migrations

```bash
php artisan migrate
```

## Updating from Previous Versions

### ⚠️ Updating to v1.4.0+

If you're updating from a previous version (especially v1.3.x or earlier), you may need to run migrations to add the `user_id` column for enhanced security and user association features:

```bash
# Update the package
composer update maccesar/laravel-dropzone-enhanced

# Run any pending migrations
php artisan migrate
```

**Note**: Starting from v1.4.0, the package includes defensive coding that makes it work with or without the `user_id` column. You'll get enhanced features like user association and improved security by running the migrations, but it's not required for basic functionality.

#### What the migration adds:
- `user_id` column to associate photos with users
- Improved security for photo deletion
- Better audit trail of who uploaded what

## Usage

Add the `HasPhotos` trait to your model:

```php
use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;

class Product extends Model
{
  use HasPhotos;
  // ...
}
```

Then use the component in your views:

```blade
<x-dropzone-enhanced::area
  :object="$product"
  directory="products"
/>
```

The component accepts the following parameters:

| Parameter     | Description                                          | Default                          |
| ------------- | ---------------------------------------------------- | -------------------------------- |
| `object`      | The model to attach photos to                        | **Required**                     |
| `directory`   | Directory where photos will be stored                | **Required**                     |
| `dimensions`  | Max dimensions for resize (format: "widthxheight")   | From config `default_dimensions` |
| `preResize`   | Whether to resize the image in browser before upload | From config `pre_resize`         |
| `maxFiles`    | Maximum number of files allowed                      | From config `max_files`          |
| `maxFilesize` | Maximum file size in MB                              | From config `max_filesize`       |

### Configuration

The package is highly configurable through the `config/dropzone.php` file. All component parameters use these centralized config values by default:

```php
return [
  'routes' => [
    'prefix' => '',  // Change route prefix if needed
    'middleware' => ['web'],
  ],
  'storage' => [
    'disk' => 'public',
    'directory' => 'images',
  ],
  'images' => [
    'default_dimensions' => '1920x1080',
    'pre_resize' => true,
    'quality' => 90,
    'max_files' => 10,
    'max_filesize' => 10000, // in KB
    'thumbnails' => [
      'enabled' => true,
      'dimensions' => '288x288',
    ],
  ],
  'security' => [
    // If true, any authenticated user can delete photos (use with caution)
    'allow_all_authenticated_users' => false,
    
    // Custom access key for API or JavaScript requests without authentication
    'access_key' => null,
  ],
];
```

## Advanced Usage

### Using with Specific Storage Disks

By default, the package uses your `public` disk. To change this:

```php
// In config/dropzone.php
'storage' => [
  'disk' => 's3',
  'directory' => 'uploads/images',
],
```

### Working with Photos

The `HasPhotos` trait adds several useful methods to your model:

```php
// Get all photos
$product->photos;

// Get the main photo
$photo = $product->mainPhoto();

// Get the URL of the main photo
$url = $product->getMainPhotoUrl();

// Get the thumbnail URL of the main photo
$url = $product->getMainPhotoThumbnailUrl('288x288');

// Set a photo as the main photo
$product->setMainPhoto($photoId);

// Check if the model has photos
if ($product->hasPhotos()) {
  // Do something
}

// Delete all photos
$product->deleteAllPhotos();
```

### Displaying Photos

To display the uploaded photos, use the photos component:

```blade
<x-dropzone-enhanced::photos
  :object="$product"
  :lightbox="true"
/>
```

This component will show all uploaded images with options to:
- View in lightbox
- Set as main image (with toggle capability)
- Delete
- Reorder by drag-and-drop

### Custom Component Options

The dropzone component accepts the following props:

- `object`: The model instance (required)
- `directory`: Storage subdirectory for the images (required)
- `dimensions`: Target dimensions for resizing images (default: from config)
- `preResize`: Whether to resize images on upload (default: from config)
- `maxFiles`: Maximum number of files allowed (default: 10)
- `maxFilesize`: Maximum file size in MB (default: 5)

## Advanced Configuration Options

#### Custom Routes and Middleware

By default, all package routes use the `web` middleware and have no prefix. You can customize this in your configuration:

```php
// config/dropzone.php
'routes' => [
  'prefix' => 'admin',  // Add a route prefix if needed
  'middleware' => ['web', 'auth:admin'],  // Add authentication or other middleware
],
```

This is particularly useful when:
- You need to protect uploads with specific middleware
- You want to integrate with your existing admin panel
- You use different route structures for different environments

## Photo URLs

The package generates URLs for your photos using relative paths:

```php
// Getting the URL for the original image
$photo->getUrl();

// Getting the URL for a thumbnail
$photo->getThumbnailUrl(); // Uses default dimensions from config
$photo->getThumbnailUrl('400x300'); // Custom dimensions
```

These URLs work consistently across different environments and domains.

## API Reference

### Photo Model

| Method                                | Description                                                     |
| ------------------------------------- | --------------------------------------------------------------- |
| `getUrl()`                            | Returns the URL for the original image using relative path      |
| `getThumbnailUrl($dimensions = null)` | Returns the URL for a thumbnail with optional custom dimensions |
| `deletePhoto()`                       | Deletes the photo and associated files from storage             |
| `getPath()`                           | Returns the relative path to the image file within storage      |

### Photo Management

The package includes several routes for photo management:

| Route                           | Method | Description                        |
| ------------------------------- | ------ | ---------------------------------- |
| `/dropzone/upload`              | POST   | Upload a new photo                 |
| `/dropzone/photos/{id}`         | DELETE | Delete a photo                     |
| `/dropzone/photos/{id}/main`    | POST   | Toggle a photo as the main image   |
| `/dropzone/photos/{id}/is-main` | GET    | Check if a photo is marked as main |
| `/dropzone/photos/reorder`      | POST   | Reorder photos                     |

You can add a custom prefix (like `/admin`) in the config file if needed.

## Security Features

### Photo Deletion Protection

Starting from version 1.2.1, the package includes security features to protect photo deletion:

- Photos can only be deleted by users who have proper authorization
- The `userCanDeletePhoto()` method in the controller verifies ownership permissions
- Multiple ownership verification mechanisms are supported:
  - User authentication check
  - Direct model ownership (via `user_id` field)
  - Custom ownership methods (`isOwnedBy()`)
  - Session tokens for public forms

When unauthorized deletion is attempted, a 403 Forbidden response is returned with an error message that is displayed to the user.

### Allowing Photo Deletion

Here are specific ways to enable photo deletion in different scenarios:

#### For Authenticated Users

If your application uses Laravel's authentication, ensure your models have a relationship to the user:

```php
// In your model (e.g., Product.php)
public function user()
{
  return $this->belongsTo(User::class);
}
```

Or simply add a `user_id` field to your model's table.

#### For Public Forms

If you need to allow deletion in public forms (without authentication), use session tokens:

```php
// When creating an entry with photos
$sessionKey = "photo_access_" . get_class($model) . "_{$model->id}";
$request->session()->put($sessionKey, true);
```

#### Using a Custom Controller

For more advanced authorization, extend the controller:

```php
use MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController;

class CustomDropzoneController extends DropzoneController
{
  protected function userCanDeletePhoto(Request $request, Photo $photo, $model)
  {
    // Example: Check if model belongs to current user
    if (auth()->check() && $model->user_id == auth()->id()) {
      return true;
    }

    // Example: Check if model has a specific status
    if ($model->status == 'draft') {
      return true;
    }

    // Example: Check against a permission system
    if (auth()->check() && auth()->user()->can('delete-photos')) {
      return true;
    }

    return false;
  }
}
```

Then, update your routes to use your custom controller:

```php
// In a service provider or routes file
Route::delete('dropzone/photos/{id}', [CustomDropzoneController::class, 'destroy'])
    ->name('dropzone.destroy');
```

### Customizing Authorization Logic

You can extend or override the authorization logic by:

1. Creating a custom controller that extends `DropzoneController`
2. Overriding the `userCanDeletePhoto()` method
3. Implementing your own authorization rules

```php
// In your custom controller
protected function userCanDeletePhoto(Request $request, Photo $photo, $model)
{
  // Your custom authorization logic
  return true; // Always allow (not recommended for production)
}
```

## Photo Deletion Authorization

The package includes a comprehensive authorization system for photo deletion. Here are all the supported authorization methods:

### For Authenticated Users

When a user is authenticated, the following checks are performed (in order):

1. **Direct Model Ownership** - If the model has a `user_id` field that matches the authenticated user's ID
   ```php
   if (isset($model->user_id) && $model->user_id === auth()->id()) {
     return true;
   }
   ```

2. **User Relationship** - If the model has a `user()` relationship that returns the authenticated user
   ```php
   if (method_exists($model, 'user') && $model->user && $model->user->id === auth()->id()) {
     return true;
   }
   ```

3. **Custom Ownership Method** - If the model implements an `isOwnedBy()` method that returns true
   ```php
   if (method_exists($model, 'isOwnedBy') && $model->isOwnedBy(auth()->user())) {
     return true;
   }
   ```

4. **Admin Check** - If the authenticated user has an `isAdmin()` method that returns true
   ```php
   if (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()) {
     return true;
   }
   ```

5. **Laravel Gates** - If the user passes a `delete-photos` gate check
   ```php
   if (method_exists(auth(), 'can') && auth()->can('delete-photos')) {
     return true;
   }
   ```

6. **Spatie Permissions** - If using the Spatie Permissions package and the user has the right permission
   ```php
   if (method_exists(auth()->user(), 'hasPermissionTo') && auth()->user()->hasPermissionTo('delete photos')) {
     return true;
   }
   ```

7. **Allow All Authenticated** - If enabled in config, any authenticated user is allowed
   ```php
   // In config/dropzone.php
   'security' => [
     'allow_all_authenticated_users' => true, // Default is false
   ],
   ```

### For Non-Authenticated Requests

When no user is authenticated, the following options are available:

1. **Session Tokens** - Creates a temporary token in the session for public forms
   ```php
   // Format 1: Using model ID
   $sessionKey = "photo_access_" . get_class($model) . "_{$model->id}";
   session()->put($sessionKey, true);
   
   // Format 2: Using photo ID (also supported)
   $sessionKey = "photo_access_" . get_class($model) . "_{$photo->id}";
   session()->put($sessionKey, true);
   ```

2. **Access Key Header** - For API or JavaScript requests
   ```php
   // In config/dropzone.php
   'security' => [
     'access_key' => env('DROPZONE_ACCESS_KEY', 'your-secret-key'),
   ]
   
   // Then in fetch or axios requests:
   headers: {
     'X-Access-Key': 'your-secret-key'
   }
   ```

3. **Custom Controller Logic** - Override the controller to implement your own authorization
   ```php
   class CustomDropzoneController extends DropzoneController
   {
     protected function userCanDeletePhoto(Request $request, Photo $photo, $model)
     {
       // Your custom logic here
       return true;
     }
   }
   ```

When authorization fails, a detailed 403 response is returned with information about why the deletion was not permitted.

## JavaScript Events

The package dispatches the following events:

- `PhotoUploaded`: When a photo is successfully uploaded
- `PhotoDeleted`: When a photo is deleted
- `MainPhotoChanged`: When the main photo is changed

## Troubleshooting

### Images not showing after upload

Make sure your storage is properly linked:

```bash
php artisan storage:link
```

### Upload errors with 422 response

If you're getting 422 Unprocessable Content errors, check:
- File size limits (both server and configuration)
- Image format is supported
- Permissions on your storage directory

### Error messages appear as [object Object]

Update to the latest version (1.2.0+) which includes improved error handling.

### Custom upload paths

If you need custom upload paths (e.g., user-specific folders), modify the directory parameter:

```blade
<x-dropzone-enhanced::area
  :object="$product"
  directory="products/{{ $product->id }}"
/>
```

## Projects Without Authentication

If your project doesn't use any authentication system, you have several options:

1. **Use Session Tokens** (simplest approach):

```php
// In your controller when showing the form with photos
public function show($id)
{
  $product = Product::findOrFail($id);

  // Create a session token to allow photo management
  $sessionKey = "photo_access_" . get_class($product) . "_{$product->id}";
  session()->put($sessionKey, true);

  return view('products.show', compact('product'));
}
```

2. **Use the Package's Access Key** (for API or JavaScript requests):

Configure a custom access key in your `config/dropzone.php`:

```php
'security' => [
  'access_key' => env('DROPZONE_ACCESS_KEY', 'your-secret-key'),
],
```

Then, when making requests to delete photos, include this key in the X-Access-Key header:

```js
fetch('/dropzone/photos/' + photoId, {
  method: 'DELETE',
  headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    'X-Access-Key': '{{ config("dropzone.security.access_key") }}'
  }
})
```

This approach is especially useful for:
- Single-page applications
- API-based projects
- Forms that don't have user authentication

3. **Create a Custom Controller**:

```php
// In your AppServiceProvider or a custom service provider
public function boot()
{
  // Override the controller
  $this->app->singleton('MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController', function ($app) {
    return new class extends \MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController {
      protected function userCanDeletePhoto(Request $request, Photo $photo, $model)
      {
        // Allow all deletions (use with caution!)
        return true;

        // OR: Check against some condition
        return $model->created_at->isToday();

        // OR: Use request data for validation
        return $request->has('secret_token') && $request->secret_token === 'your-secret-key';
      }
    };
  });
}
```

## Support

If you discover any issues with this package, including bugs, feature requests, or questions, please create an issue on the [GitHub repository](https://github.com/maccesar/laravel-dropzone-enhanced/issues).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Mac Cesar](https://github.com/maccesar)
- [All Contributors](../../contributors)
- [Dropzone.js](https://dropzone.dev/) - The core library

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.
