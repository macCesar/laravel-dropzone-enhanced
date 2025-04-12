# Laravel Dropzone Enhanced

[![Latest Version on Packagist](https://img.shields.io/packagist/v/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![Total Downloads](https://img.shields.io/packagist/dt/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)
[![License](https://img.shields.io/packagist/l/maccesar/laravel-dropzone-enhanced.svg?style=flat-square)](https://packagist.org/packages/maccesar/laravel-dropzone-enhanced)

A powerful and customizable Laravel package that enhances Dropzone.js to provide elegant and efficient image uploading, processing, and management for your Laravel 8+ applications.

## Features

- 🚀 **Simple Integration**: Easily add Dropzone to any model with a simple trait
- 🖼️ **Image Processing**: Resize, crop, and optimize images with [Laravel Glide Enhanced](https://github.com/maccesar/laravel-glide-enhanced) (optional)
- 🔄 **Drag & Drop Reordering**: Intuitive drag-and-drop interface for sorting images
- 🌟 **Main Image Selection**: Designate a main image for your models with toggle capability
- 🔍 **Lightbox Preview**: View full-size images with an integrated lightbox
- 📱 **Responsive Design**: Works beautifully on any device or screen size
- 🌐 **Multi-language Support**: Built-in translations for English and Spanish
- ⚡ **Local Assets**: No external CDN dependencies for improved reliability
- 🎨 **Customizable**: Extensively configurable through a simple config file
- 🛡️ **Secure**: Built with security best practices

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

## Basic Usage

### Step 1: Add the HasPhotos Trait to Your Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;

class Product extends Model
{
  use HasPhotos;

  // Your existing model code...
}
```

### Step 2: Add the Component to Your Blade View

```blade
<x-dropzone-enhanced::component
  :object="$product"
  directory="products"
  dimensions="1920x1080"
  :preResize="true"
  :maxFiles="10"
  :maxFilesize="5"
/>
```

## Configuration

You can configure the package by modifying the published config file at `config/dropzone.php`:

```php
return [
  'routes' => [
    'prefix' => 'admin',
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
    'max_filesize' => 5000, // in KB
    'thumbnails' => [
      'enabled' => true,
      'dimensions' => '288x288',
    ],
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

### Image Processing with Laravel Glide Enhanced

For advanced image processing, install the optional Laravel Glide Enhanced package:

```bash
composer require maccesar/laravel-glide-enhanced
```

The package will automatically detect and use it for image processing.

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

### Custom Component Options

The dropzone component accepts the following props:

- `object`: The model instance (required)
- `directory`: Storage subdirectory for the images (required)
- `dimensions`: Target dimensions for resizing images (default: from config)
- `preResize`: Whether to resize images on upload (default: from config)
- `maxFiles`: Maximum number of files allowed (default: 10)
- `maxFilesize`: Maximum file size in MB (default: 5)

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

| Route                                 | Method | Description                        |
| ------------------------------------- | ------ | ---------------------------------- |
| `/admin/dropzone/upload`              | POST   | Upload a new photo                 |
| `/admin/dropzone/photos/{id}`         | DELETE | Delete a photo                     |
| `/admin/dropzone/photos/{id}/main`    | POST   | Toggle a photo as the main image   |
| `/admin/dropzone/photos/{id}/is-main` | GET    | Check if a photo is marked as main |
| `/admin/dropzone/photos/reorder`      | POST   | Reorder photos                     |

The prefix `/admin` can be configured in the config file.

## JavaScript Events

The package dispatches the following events:

- `PhotoUploaded`: When a photo is successfully uploaded
- `PhotoDeleted`: When a photo is deleted
- `MainPhotoChanged`: When the main photo is changed

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
