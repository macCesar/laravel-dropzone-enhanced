# Laravel Dropzone Enhanced

[![Latest Version on Packagist](https://img.shields.io/packagist/v/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![Total Downloads](https://img.shields.io/packagist/dt/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![License](https://img.shields.io/packagist/l/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)

A powerful and customizable Laravel package that enhances Dropzone.js to provide elegant and efficient image uploading, processing, and management for your Laravel 8+ applications.

## Features

- **Simple Integration**: Easily add Dropzone to any model with a simple trait
- **Image Processing**: Resize, crop, and optimize images with [Laravel Glide Enhanced](https://github.com/maccesar/laravel-glide-enhanced) (optional)
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
    'middleware' => ['web', 'auth'],
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
];

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

### Image Processing with Laravel Glide Enhanced

For advanced image processing, install the optional Laravel Glide Enhanced package:

```bash
composer require maccesar/laravel-glide-enhanced
```

The package will automatically detect and use Laravel Glide Enhanced for image processing, which provides:

- Dynamic image resizing and cropping
- Format conversion (WebP, JPG, PNG)
- Image optimization
- Automatic caching for improved performance
- Watermarking capabilities

Example of advanced usage with Laravel Glide Enhanced:

```php
// In your controller or view
use MacCesar\LaravelGlideEnhanced\Facades\ImageProcessor;

// Create optimized WebP thumbnails
$optimizedUrl = ImageProcessor::webpUrl($photo->getPath(), [
  'q' => 90,
  'w' => 300,
  'h' => 300,
  'fit' => 'crop'
]);

// Use predefined presets from the Laravel Glide Enhanced configuration
$thumbnailUrl = ImageProcessor::preset($photo->getPath(), 'thumbnail');

// Generate responsive srcset attributes
$srcset = ImageProcessor::srcset($photo->getPath(), [
  'w' => 600,
  'fm' => 'webp'
]);
```

For complete documentation, visit the [Laravel Glide Enhanced repository](https://github.com/maccesar/laravel-glide-enhanced).

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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Mac Cesar](https://github.com/maccesar)
- [All Contributors](../../contributors)
- [Dropzone.js](https://dropzone.dev/) - The core library

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email contact@maccesar.com instead of using the issue tracker.
