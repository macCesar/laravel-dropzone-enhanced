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
- **Broad Compatibility**: Supports Laravel 8, 9, 10, and 11.

## Requirements

- PHP 7.4 or higher
- Laravel 8.0 or higher

## Installation

**1. Install via Composer**
```bash
composer require maccesar/laravel-dropzone-enhanced
```

**2. Run the Installer**
This command publishes the config file, migrations, and assets.
```bash
php artisan dropzone-enhanced:install
```

**3. Run Migrations**
```bash
php artisan migrate
```

**4. Link Storage**
Ensure your public storage disk is linked so images are accessible.
```bash
php artisan storage:link
```

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
            <input type="text" id="name" name="name" value="{{ $product->name }}">
        </div>

        <hr>

        {{-- 1. MANAGE EXISTING PHOTOS --}}
        <h3>Manage Existing Photos</h3>
        <p>Drag to reorder, click the star to set the main photo, or use the trash icon to delete.</p>
        <x-dropzone-enhanced::photos
            :model="$product"
            :lightbox="true"
        />

        <hr>

        {{-- 2. UPLOAD NEW PHOTOS --}}
        <h3>Add New Photos</h3>
        <x-dropzone-enhanced::area
            :model="$product"
            directory="products"
            :max-files="10"
            :max-filesize="5"
        />

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

| Parameter     | Type     | Description                                                                 | Default                                        |
| :------------ | :------- | :-------------------------------------------------------------------------- | :--------------------------------------------- |
| `:model`      | `Model`  | **Required.** The Eloquent model instance to attach photos to.              |                                                |
| `directory`   | `string` | **Required.** The subdirectory within your storage disk to save the images. |                                                |
| `dimensions`  | `string` | Max dimensions for resize (e.g., "1920x1080").                              | `config('dropzone.images.default_dimensions')` |
| `preResize`   | `bool`   | Whether to resize the image in the browser before upload.                   | `config('dropzone.images.pre_resize')`         |
| `maxFiles`    | `int`    | Maximum number of files allowed to be uploaded.                             | `config('dropzone.images.max_files')`          |
| `maxFilesize` | `int`    | Maximum file size in MB.                                                    | `config('dropzone.images.max_filesize')`       |

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
// Get all associated photos as a Collection
$product->photos;

// Get the main photo model instance
$photo = $product->mainPhoto();

// Get the URL of the main photo
$url = $product->getMainPhotoUrl();

// Get the thumbnail URL of the main photo
$thumbUrl = $product->getMainPhotoThumbnailUrl();

// Set a specific photo as the main one
$product->setMainPhoto($photoId);

// Check if the model has any photos
if ($product->hasPhotos()) {
  // ...
}

// Delete all photos associated with the model
$product->deleteAllPhotos();
```

### Configuration

For deep customization, publish the configuration file:
```bash
php artisan vendor:publish --tag=dropzone-enhanced-config
```
You can now edit `config/dropzone.php` to change default image sizes, storage disks, route middleware, and more.

### Security & Authorization

The package includes a comprehensive and robust authorization system for photo deletion to prevent unauthorized actions. It performs a series of checks for authenticated users (model ownership, `isAdmin` methods, Gates) and provides secure options for unauthenticated scenarios (session tokens, access keys).

For full details on customizing authorization logic, please refer to the extensive comments in the `config/dropzone.php` file and the source code of the `DropzoneController`.

## Troubleshooting

**Images not showing after upload?**
- Run `php artisan storage:link`.
- Check file permissions on your `storage/app/public` directory.
- Verify the `disk` and `directory` settings in `config/dropzone.php`.

**Getting 422 or 500 errors on upload?**
- Check your browser's developer console for a more specific error message from Dropzone.
- Ensure the `max_filesize` in your config doesn't exceed server limits (`upload_max_filesize` in `php.ini`).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
