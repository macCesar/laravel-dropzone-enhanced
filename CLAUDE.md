# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Package Installation
```bash
# Install package in Laravel project
composer require maccesar/laravel-dropzone-enhanced

# Auto-install package with all assets
php artisan dropzone-enhanced:install

# Manually publish individual components
php artisan vendor:publish --tag=dropzone-enhanced-config
php artisan vendor:publish --tag=dropzone-enhanced-migrations
php artisan vendor:publish --tag=dropzone-enhanced-assets
php artisan vendor:publish --tag=dropzone-enhanced-views
php artisan vendor:publish --tag=dropzone-enhanced-lang

# Run migrations
php artisan migrate
```

### Testing Commands
```bash
# Run PHPUnit tests (if test suite exists)
vendor/bin/phpunit

# Run with Orchestra Testbench
vendor/bin/phpunit --testdox
```

### Development Tools
```bash
# Link storage (required for image display)
php artisan storage:link
```

## Architecture Overview

This is a Laravel package that provides enhanced Dropzone.js functionality for file uploads with image processing, thumbnails, and photo management.

### Core Components

**Service Provider** (`src/DropzoneServiceProvider.php`)
- Registers package configuration, routes, views, translations, and migrations
- Auto-discovers migrations and publishes assets
- Provides the `dropzone-enhanced:install` command

**Photo Model** (`src/Models/Photo.php`)
- Polymorphic model that can attach to any model via `photoable_*` fields
- Handles image URLs, thumbnails, and file deletion
- Includes user association (`user_id`) for security
- Properties: `filename`, `original_filename`, `disk`, `directory`, `extension`, `mime_type`, `size`, `width`, `height`, `sort_order`, `is_main`

**HasPhotos Trait** (`src/Traits/HasPhotos.php`)
- Add to any model to enable photo attachment: `use HasPhotos;`
- Provides methods: `photos()`, `mainPhoto()`, `getMainPhotoUrl()`, `setMainPhoto()`, `hasPhotos()`, `deleteAllPhotos()`
- Auto-deletes photos when parent model is deleted

**DropzoneController** (`src/Http/Controllers/DropzoneController.php`)
- Handles upload, delete, reorder, and main photo operations
- Includes comprehensive authorization system for photo deletion
- Validates file uploads and processes images with thumbnails

**ImageProcessor Service** (`src/Services/ImageProcessor.php`)
- Generates thumbnails with proper aspect ratio and cropping
- Supports JPEG, PNG, GIF formats
- Creates organized thumbnail directory structure

### Database Schema

The `photos` table uses:
- Polymorphic relationship (`photoable_id`, `photoable_type`)
- User association (`user_id`) for security
- File metadata (dimensions, size, mime type, etc.)
- Sorting and main photo designation

### Configuration System

Main config file: `config/dropzone.php`
- **Routes**: Prefix and middleware configuration
- **Storage**: Disk and directory settings
- **Images**: Dimensions, quality, file limits, thumbnail settings
- **Security**: Authorization and access control

### Frontend Components

Blade components in `resources/views/components/`:
- `area.blade.php` - Main dropzone upload area
- `photos.blade.php` - Display uploaded photos
- `lightbox.blade.php` - Image lightbox viewer
- `component.blade.php` - Core JavaScript functionality

Assets in `resources/assets/`:
- `dropzone-min.js` - Dropzone.js library
- `dropzone.css` - Styling

### Routes Structure

All routes prefixed with configurable prefix (default: none):
- `POST /dropzone/upload` - File upload
- `DELETE /dropzone/photos/{id}` - Delete photo
- `POST /dropzone/photos/{id}/main` - Toggle main photo
- `GET /dropzone/photos/{id}/is-main` - Check main status
- `POST /dropzone/photos/reorder` - Reorder photos

### Security Features

**Authorization System** for photo deletion:
1. Direct model ownership (`user_id` field)
2. User relationship method (`user()`)
3. Custom ownership method (`isOwnedBy()`)
4. Admin check (`isAdmin()`)
5. Laravel Gates (`delete-photos`)
6. Spatie Permissions support
7. Session tokens for public forms
8. Access key header for API requests
9. Custom controller override capability

## Usage Patterns

### Basic Integration
```php
// In your model
use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;
class Product extends Model {
    use HasPhotos;
}

// In your Blade view
<x-dropzone-enhanced::area :model="$product" directory="products" />
<x-dropzone-enhanced::photos :model="$product" :lightbox="true" />
```

### Working with Photos
```php
$product->photos;                    // Get all photos
$product->mainPhoto();               // Get main photo
$product->getMainPhotoUrl();         // Get main photo URL
$product->setMainPhoto($photoId);    // Set main photo
$product->hasPhotos();               // Check if has photos
$product->deleteAllPhotos();         // Delete all photos
```

### Photo URLs and Thumbnails
```php
$photo->getUrl();                    // Original image URL
$photo->getThumbnailUrl();           // Default thumbnail URL
$photo->getThumbnailUrl('400x300');  // Custom size thumbnail
$photo->deletePhoto();               // Delete photo and files
```

## File Structure Conventions

- Package follows Laravel package conventions
- Views use `dropzone-enhanced::` namespace
- Config uses `dropzone` key
- Translations use `dropzone-enhanced` namespace
- Assets published to `public/vendor/dropzone-enhanced/`
- Images stored in configurable directory (default: `storage/app/public/images/`)
- Thumbnails organized in `thumbnails/{dimensions}/` subdirectories

## Development Notes

- Package supports Laravel 8+ and PHP 7.4+
- Uses polymorphic relationships for maximum flexibility
- Includes comprehensive authorization system
- Supports multiple storage disks
- Handles thumbnail generation without external dependencies
- Provides extensive configuration options
- Includes translation support (English/Spanish)

## Asset Management

This package uses NPM to manage Dropzone.js assets for version control and source map support.

### Updating Dropzone.js

```bash
# Update to latest version
npm run update-dropzone

# Or manually update
npm install dropzone@latest
npm run build-assets
```

### Build Process

The `build-assets.js` script copies the following files from `node_modules/dropzone/dist/` to `resources/assets/`:
- `dropzone-min.js` (minified JavaScript)
- `dropzone-min.js.map` (source map for debugging)
- `dropzone.css` (styles)  
- `dropzone.css.map` (CSS source map)

Current Dropzone.js version: **6.0.0-beta.2**

### Files Structure
```
├── package.json          # NPM dependencies and scripts
├── build-assets.js       # Asset build script
├── node_modules/dropzone/ # NPM installed Dropzone.js
└── resources/assets/     # Built assets (published to public/vendor/)