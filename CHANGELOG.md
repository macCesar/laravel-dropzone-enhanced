# Changelog

All notable changes to `laravel-dropzone-enhanced` will be documented in this file.

## 2.1.6 - 2025-09-02

### 🚀 URL Generation Optimization

#### Fixed
- **Simplified URL generation**: Replaced complex URL building logic with Laravel's native `Storage::disk()->url()` method
- **Improved consistency**: All image and thumbnail URLs now use Laravel's built-in storage URL generation
- **Better maintainability**: Removed custom domain handling that was prone to configuration issues

#### Technical Improvements
- Streamlined `buildUrl()` method in Photo model to use Laravel native functionality
- Eliminated dependency on manual request domain parsing
- Enhanced reliability across different hosting environments (localhost, Herd, Valet, production)

#### Benefits
- ✅ **More reliable**: URLs automatically match Laravel's APP_URL configuration
- ✅ **Less complex**: Removed 10+ lines of custom logic in favor of 1-line Laravel method
- ✅ **Better compatibility**: Works consistently across all Laravel storage configurations
- ✅ **Easier debugging**: Uses standard Laravel URL generation patterns

## 2.1.4 - 2025-08-29

### 🔄 Command Prefix Update (Backward Compatible)

#### Changed
- Renamed Artisan command to use a hyphen-less prefix: `dropzoneenhanced:install`.
- Kept a backward-compatible alias: `dropzone-enhanced:install` continues to work.
 - Added dual vendor:publish tags for all publishable assets: both `dropzoneenhanced-*` and legacy `dropzone-enhanced-*` are registered.

#### Updated
- Documentation references in README, CLAUDE.md, AGENTS.md, and docs updated to prefer the new command.
 - Docs updated to prefer `vendor:publish --tag=dropzoneenhanced-*` with alias notes.

#### Upgrade Notes
- No breaking changes. Existing automation and docs that call `php artisan dropzone-enhanced:install` keep working.
- For consistency, update scripts to `php artisan dropzoneenhanced:install` when convenient.

## 2.1.3 - 2025-08-28

### 🔧 EXIF Orientation Fix

#### Added
- **EXIF orientation correction**: Automatic correction for mobile photos showing rotated in lightbox
- **Smart image processing**: Images from mobile devices now display correctly oriented regardless of capture orientation
- **New ImageProcessor methods**: Added `correctImageOrientation()` and `correctOriginalImageInPlace()` methods
- **Enhanced logging**: Improved EXIF processing logging with detailed orientation information
- **ext-exif dependency**: Added PHP EXIF extension requirement for orientation detection

#### Fixed
- **Mobile photo orientation**: Fixed photos from mobile devices showing rotated in lightbox view
- **Original image processing**: Original images now processed for EXIF orientation (thumbnails were already working)
- **Cross-device compatibility**: Ensures consistent image display across different devices and orientations

#### Changed
- **Requirements**: Added `ext-exif` extension requirement in composer.json
- **Documentation**: Updated README.md with EXIF Orientation Support section
- **Image processing**: Enhanced ImageProcessor with comprehensive EXIF orientation handling

#### Technical Details
```php
// New methods in ImageProcessor
public static function correctImageOrientation($image, $filePath)  // Handles 8 EXIF orientations
public static function correctOriginalImageInPlace($filePath, $mimeType)  // Applies correction to stored files

// Automatic processing in DropzoneController after upload
if (in_array($file->getMimeType(), ['image/jpeg', 'image/jpg']) && function_exists('exif_read_data')) {
    ImageProcessor::correctOriginalImageInPlace($originalPath, $file->getMimeType());
}
```

#### Benefits
- 📱 **Mobile-first**: Perfect photo orientation from mobile uploads
- 🔄 **Backward compatible**: No breaking changes, works with existing installations
- 🎯 **Performance optimized**: Only processes JPEG images with EXIF data
- 🛡️ **Graceful fallbacks**: Handles images without EXIF data safely

#### EXIF Orientations Supported
- Orientation 1: Normal (no change needed)
- Orientation 2: Flip horizontal
- Orientation 3: Rotate 180 degrees
- Orientation 4: Flip vertical
- Orientation 5: Rotate 90° CCW + flip horizontal
- Orientation 6: Rotate 90° counter-clockwise
- Orientation 7: Rotate 90° CW + flip horizontal
- Orientation 8: Rotate 90° clockwise

## 2.1.2 - 2025-07-30

### 🔧 Asset Management Enhancement

#### Added
- **NPM-based asset management**: Added `package.json` for Dropzone.js dependency management
- **Build automation**: New `scripts/build-assets.js` for copying assets from node_modules
- **Source map support**: Fixed missing source map files (.map) for better debugging
- **Version tracking**: Now explicitly tracks Dropzone.js version (currently 6.0.0-beta.2)

#### Changed
- **Asset workflow**: Moved from manual asset bundling to NPM-managed dependencies
- **Build process**: Assets now built from `node_modules/dropzone/dist/` instead of manual copies
- **Developer experience**: Source maps now properly available for debugging

#### New NPM Scripts
```bash
npm run build-assets      # Copy assets from node_modules to resources/assets
npm run update-dropzone   # Update Dropzone.js to latest version + build
```

#### Technical Benefits
- 🎯 **Version control**: Explicit Dropzone.js version management
- 🐛 **Better debugging**: Source maps resolve 404 errors in DevTools
- 🔄 **Easy updates**: Simple `npm run update-dropzone` command
- 📦 **Professional workflow**: NPM-based dependency management

#### Migration for Contributors
For developers working on this package:
1. Run `npm install` to get dependencies
2. Use `npm run build-assets` to rebuild assets
3. Use `npm run update-dropzone` for Dropzone.js updates

## 2.1.0 - 2025-01-30

### 🚀 Enhanced Image Processing API

#### Added
- **Dynamic image processing**: `getUrl()` now accepts dimensions, format, and quality parameters
- **Format conversion**: Support for WebP, PNG, JPG, GIF output formats
- **Custom quality control**: Specify image quality (0-100) for each processed image
- **Intelligent cropping**: Smart aspect ratio preservation with center cropping
- **Automatic generation**: Images are generated on-demand and cached for future requests

#### Changed
- **BREAKING**: `getThumbnailUrl()` now only uses default config values (no parameters)
- **BREAKING**: `getMainPhotoThumbnailUrl()` in HasPhotos trait no longer accepts parameters
- **Enhanced**: `getUrl()` is now the primary method for all custom image processing
- **Improved**: Better file organization with format-specific subdirectories

#### New API Examples
```php
// Simple usage (unchanged)
$photo->getUrl();                    // Original image
$photo->getThumbnailUrl();           // Default thumbnail from config

// Advanced processing (NEW)
$photo->getUrl('400x400');           // Square 400x400
$photo->getUrl('800x600', 'webp');   // WebP format
$photo->getUrl('400x400', 'jpg', 85); // Custom quality

// Migration required for HasPhotos trait
// OLD: $product->getMainPhotoThumbnailUrl('400x400', 'webp')
// NEW: $product->mainPhoto()?->getUrl('400x400', 'webp')
```

#### Benefits
- 🎯 **More intuitive API**: One method for all processing needs
- 🚀 **Better performance**: Generate only what you need, when you need it
- 🎨 **Format flexibility**: Easy WebP adoption for better compression
- 📁 **Organized storage**: Clean file structure for processed images

#### Migration Guide
For existing code using custom thumbnail parameters:
```php
// Replace this pattern:
$url = $model->getMainPhotoThumbnailUrl('400x400', 'webp', 85);

// With this pattern:
$mainPhoto = $model->mainPhoto();
$url = $mainPhoto?->getUrl('400x400', 'webp', 85);
```

### Technical Details
- Enhanced `ImageProcessor` with format conversion support
- New private `processImage()` method for internal processing logic
- Improved thumbnail path organization with format suffixes
- Better error handling and fallbacks for failed processing

## 1.5.0 - 2025-07-28

### Added
- Laravel 11 support in composer.json
- Enhanced compatibility with latest Laravel versions

### Changed
- Updated framework dependencies to support Laravel 8-12
- Improved PHP version compatibility (^7.4|^8.0|^8.2)

## 1.4.4 - 2025-07-XX

### Fixed
- Minor bug fixes and stability improvements
- Documentation corrections

## 1.4.3 - 2025-07-XX

### Fixed
- Minor bug fixes and stability improvements

## 1.4.2 - 2025-07-XX

### Fixed
- Bug fixes related to user association features
- Enhanced error handling

## 1.4.1 - 2025-07-XX

### Fixed
- Minor bug fixes and performance improvements
- Documentation updates

## 1.4.0 - 2025-07-05 🚀 MAJOR ENHANCEMENT RELEASE

### Added
- **Native thumbnail generation**: Added standalone thumbnail creation using PHP GD extension
- **ImageProcessor service**: New service class for image processing without external dependencies
- **Enhanced Photo model**: Added defensive coding for backward compatibility with `user_id` field
- **Improved URL generation**: Fixed double slash issues in generated URLs
- **Better defensive coding**: Package now works with or without `user_id` column in existing installations

### Removed
- **Complete Laravel Glide Enhanced decoupling**: Package now works entirely independently
- **Removed all external dependencies**: No longer requires Laravel Glide Enhanced for any functionality
- **Cleaned up unnecessary commands**: Removed CheckDropzoneUpdate and other maintenance commands

### Changed
- **Standalone functionality**: Package is now completely self-contained
- **Native image processing**: Uses GD extension for thumbnail generation (supports JPEG, PNG, GIF, WebP)
- **Enhanced backward compatibility**: Existing installations continue working without any breaking changes
- **Simplified installation**: Clean migration structure without conflicts
- **Improved URL handling**: Fixed path construction issues with rtrim() usage

### Fixed
- **Critical compatibility issues**: Package now works on public sites and existing installations
- **Migration conflicts**: Removed problematic duplicate migrations
- **URL generation**: Fixed double slash issues in photo URLs
- **Thumbnail generation**: Restored missing thumbnail functionality
- **Permission handling**: Enhanced user permission checks with graceful fallbacks

### Technical Improvements
- Added ImageProcessor service for native thumbnail generation
- Enhanced DropzoneController with robust error handling
- Improved Photo model with defensive `user_id` handling
- Cleaner ServiceProvider with only essential commands
- Better configuration management
- Comprehensive error handling and fallbacks

### Migration Notes
- **New installations**: Get clean, complete database structure from the start
- **Existing installations**: Continue working without interruption (defensive code handles missing columns)
- **No manual migration needed**: Package handles compatibility automatically

## 1.3.0 - 2025-04-12

### Added
- Added comprehensive authorization options for photo deletion
- Added `security` section to configuration with `allow_all_authenticated_users` and `access_key` options
- Added support for X-Access-Key header for API authorization
- Added multiple ownership validation methods (user relationship, isAdmin, Gates, Spatie Permissions)
- Added detailed documentation on all authorization methods in README
- Added new configuration options for image processing in `dropzone.php`, including default dimensions, quality settings, and thumbnail configuration
- Added improved drag-and-drop functionality using Sortable.js in `photos.blade.php`

### Changed
- Enhanced error messages in the `destroy` method with detailed context information
- Updated `userCanDeletePhoto` method with more robust permission checks
- Changed README to use GitHub Issues instead of email for support requests
- Improved security by removing personal contact information from documentation
- Refactored `area.blade.php` to streamline Dropzone initialization and improve error handling
- Updated `lightbox.blade.php` to simplify lightbox functionality and improve accessibility
- Enhanced `photos.blade.php` with new styles for photo actions
- Modified `web.php` to adjust route prefixes and middleware for Dropzone routes
- Improved DropzoneController with enhanced authorization checks for photo deletion and main photo setting

### Fixed
- Fixed session token validation to check both model ID and photo ID formats
- Improved error handling for unauthorized deletion attempts

## 1.2.0 - 2025-04-11

### Added
- Improved error handling with detailed error messages
- Enhanced client-side resizing configuration
- More flexible configuration defaults from config file

### Changed
- Removed default `/admin` route prefix for greater flexibility
- Changed default middleware to only use `web` (without `auth`)
- Improved client-side validation and error display
- Updated documentation with comprehensive configuration examples

### Fixed
- Fixed empty file data sent to server during upload
- Corrected duplicate error handler in Dropzone initialization
- Improved handling of large file uploads
- Fixed installation command prompt issue

## 1.1.0 - 2025-04-11

### Added
- New `checkIsMain` endpoint to verify if an image is marked as the main one
- Toggle capability for the main image button (activate/deactivate)
- Visual indicator for drag function (drag handle)

### Fixed
- Fixed URL generation to use relative paths instead of absolute ones
- Corrected image proportion in thumbnails with `object-fit: contain`
- Improved user interface for reordering images

## 1.0.0 - 2025-04-11

### Added
- Initial release with core functionality
- Dropzone.js integration with local assets (version 6.0.0-beta.2)
- Photo model with polymorphic relationships
- HasPhotos trait for model integration
- Image processing with Laravel Glide Enhanced (optional)
- Thumbnail generation
- Photo reordering with drag and drop
- Main photo selection
- Photo deletion
- Lightbox for image viewing
- Blade components for easy integration
- Multi-language support (English and Spanish)
- Comprehensive documentation
- Installation command

### Configuration Options
- Route prefix and middleware
- Storage disk and directory
- Image dimensions and quality
- Thumbnail settings
- Pre-resize options
