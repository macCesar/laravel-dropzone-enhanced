# Changelog

All notable changes to `laravel-dropzone-enhanced` will be documented in this file.

## 2.7.0 - 2026-03-01

### ⚠️ Breaking Change — Thumbnail Folder Structure

Format is no longer included in the cache folder name. The file extension already conveys the format — the `_webp` / `_jpg` suffix in folder names was redundant.

|                 | Before                                          | After                                      |
| --------------- | ----------------------------------------------- | ------------------------------------------ |
| WebP thumb      | `cache/products/16/462x700_webp/photo.webp`     | `cache/products/16/462x700/photo.webp`     |
| JPG thumb       | `cache/products/16/462x700_jpg/photo.jpg`       | `cache/products/16/462x700/photo.jpg`      |
| Non-center crop | `cache/products/16/462x700_webp_top/photo.webp` | `cache/products/16/462x700_top/photo.webp` |

**Benefits:**
- All format variants of the same dimensions share one folder — easier to inspect and manage
- Cleaner, less redundant paths
- `rm -rf cache/products/16/462x700/` clears all format variants of that size at once

**Upgrade steps:**
```bash
# Clear old thumbnails (they will regenerate on demand)
rm -rf storage/app/public/cache

# Or pre-generate with your warm command
php artisan products:warm-images
```

---

## 2.6.0 - 2026-03-01

### Added

- **Upload-time image warming** via three new props on `<x-dropzone-enhanced::area />`:
  - `warmSizes` — array of dimension strings (`'462'`, `'1200x675'`) to pre-generate immediately at upload time. Same syntax as `src()` / `srcset()`.
  - `warmFactor` — integer multiplier (1–5). `warmFactor=2` generates 1× and 2× for each size, matching `srcset()`'s `$multipliers` argument.
  - `warmFormat` — output format (`'webp'`, `'jpg'`, `'png'`).
- **Three new config keys** under `images` in `config/dropzone.php`:
  ```php
  'warm_sizes'  => [],      // e.g. ['462', '96', '1200x675']
  'warm_factor' => 1,
  'warm_format' => 'webp',
  ```
- **New validation rules** in `DropzoneController::upload()` for `warm_sizes`, `warm_factor`, and `warm_format`.

### How it works

Warm generation reuses `$photo->srcset($dim, $factor, $format)` which already generates all size variants via the existing `ImageProcessor` pipeline. No new image-processing logic was added.

```blade
<x-dropzone-enhanced::area
  :model="$post"
  directory="blog/{{ $post->id }}"
  :warmSizes="['1200x675', '800x450', '416x234']"
  :warmFactor="2"
  warmFormat="webp"
/>
{{-- Generates 6 thumbnails at upload time: 3 sizes × 2x multiplier --}}
```

### Performance note

Thumbnail generation is synchronous within the upload request. For typical configurations (3–5 sizes × factor 2–3 = 6–15 thumbnails) the added time is < 3 seconds per photo — an acceptable trade-off versus slow first-render on-demand generation.

### Backward compatibility

All new props default to their config values (empty `warm_sizes` → no warm generation). Existing dropzones are completely unaffected.

---

## 2.5.0 - 2026-02-28

### ⚠️ Breaking Change — Thumbnail Storage Location

All generated thumbnails are now stored in a **central `cache/` directory** instead of `thumbnails/` subfolders scattered next to each original photo.

|         | Before                                           | After                                  |
| ------- | ------------------------------------------------ | -------------------------------------- |
| Path    | `products/16/thumbnails/462x700_webp/photo.webp` | `cache/products/16/462x700/photo.webp` |
| Cleanup | Find every `thumbnails/` folder manually         | `rm -rf storage/app/public/cache`      |

**Upgrade steps:**
```bash
# 1. Clear old thumbnails (scattered thumbnails/ folders)
php artisan dropzoneenhanced:clear-thumbnails --force

# 2. Regenerate in new central location
php artisan products:warm-images   # or your equivalent command
```

### Added
- **`dropzoneenhanced:clear-thumbnails` Artisan command** — deletes the entire thumbnail cache directory in one operation:
  ```bash
  php artisan dropzoneenhanced:clear-thumbnails          # with confirmation
  php artisan dropzoneenhanced:clear-thumbnails --force  # skip prompt
  php artisan dropzoneenhanced:clear-thumbnails --disk=s3 --force
  ```
- **`storage.thumbnail_cache_path` config key** — customize the central cache directory (default: `cache`):
  ```php
  'storage' => [
      'thumbnail_cache_path' => 'cache',
  ],
  ```

### Fixed
- **`srcset()` integer rounding mismatch**: Heights in multi-step srcsets were calculated by multiplying the 1x rounded height (`699 × 2 = 1398`), which didn't match independently calculated heights (`round(2098 × 924/1386) = 1399`). This caused pre-generated thumbnails to be bypassed (1–2px folder name mismatch), triggering slow on-demand generation. Heights are now recalculated from the original photo dimensions for each multiplier step.

## 2.4.3 - 2025-12-22

### Fixed
- 🐛 **Multi-zone Dropzone Init**: Initialize Dropzone on every `.dropzone-container` so all locale zones respond to click and drag-and-drop, not just the first instance.
- 🖼️ **Browser pre-resize quality**: Replaced Dropzone's built-in `resizeWidth/resizeHeight/resizeQuality` options (which use low-quality canvas interpolation) with a custom `transformFile` hook that sets `ctx.imageSmoothingQuality = 'high'` before drawing. This enables Lanczos/bicubic interpolation in modern browsers, producing sharp resized images without aliasing artifacts. The `pre_resize` config option now resizes with high quality instead of degrading it.

## 2.4.2 - 2025-12-22

### Fixed
- 🐛 **Crop Position Config Bug**: Fixed an issue where the `crop_position` configuration in `config/dropzone.php` was being ignored because the default value in method signatures was hardcoded to `'center'`. Now it correctly respects your configuration.

### Added
- **Dynamic Crop Position Override**: Added the ability to override `crop_position` on a per-call basis in:
  - `getMainPhotoThumbnailUrl($dimensions, $cropPosition)`
  - `getMainPhotoUrl($dimensions, $format, $quality, $cropPosition)`
  - `src($dimensions, $format, $quality, $cropPosition)`
  - `srcset(..., $cropPosition)`
  - `srcFromPath(..., $cropPosition)`

## 2.4.1 - 2025-12-19

### Changed
- Translation overrides now publish to `resources/lang/vendor/dropzone-enhanced` and are loaded when present.

## 2.4.0 - 2025-12-19

### Added
- **Photo Manager component** for compact multilingual workflows (expandable dropzones + unified gallery).
- **Drag between locales**: move photos across locale grids to reassign language.
- **Empty locale drop targets**: locale sections stay valid drop zones even with zero photos.
- **Lightbox navigation**: prev/next buttons, keyboard arrows, and image counter in lightbox.
- **Locale update endpoint**: new `dropzone.updateLocale` route for locale reassignment.

### Changed
- Updated translations and README to document the new photo manager and lightbox behavior.

## 2.3.1 - 2025-12-18

### Fixed
- 🐛 **Multilingual Photos Bug**: Fixed JavaScript not finding photo containers when using locale parameter
  - Container IDs are now dynamic (`photos-container-en`, `photos-container-default`)
  - JavaScript now initializes ALL containers with class `.photos-container`
  - Lightbox, drag-drop, and photo actions now work correctly with multiple locales
  - Fixed "Photos container not found" console error

**Impact**: This bug prevented the lightbox, reordering, and photo actions from working when using multilingual images.

**Root cause**: JavaScript was hardcoded to find `getElementById('photos-container')` but actual IDs include locale suffix.

## 2.3.0 - 2025-12-18

### Changed - Migration Strategy 🔄

**BREAKING CHANGE:** Laravel Dropzone Enhanced now relies exclusively on automatic migration loading via `loadMigrationsFrom()`.

- ❌ **REMOVED**: Migration publishing from `dropzoneenhanced:install` command
- ❌ **REMOVED**: `dropzone-enhanced-migrations` and `dropzoneenhanced-migrations` publish tags from ServiceProvider
- ✅ **IMPROVED**: Migrations now load automatically from package (always up-to-date)

**What this means for you:**

**For NEW installations:**
- No changes needed - migrations work automatically with `php artisan migrate`

**For EXISTING installations:**
- If you have published Dropzone Enhanced migrations in `database/migrations/`, they are now **redundant**
- You can safely delete published `create_photos_table.php` and locale migrations (Laravel will use package migrations instead)
- Future package updates will automatically include migration updates (no manual republishing needed)

**Benefits:**
- ✅ Migrations always match your installed package version
- ✅ No need to republish migrations when updating the package
- ✅ Cleaner project structure (fewer files in your migrations folder)
- ✅ Consistent with AdminKit behavior

**Technical details:**
- Package uses `loadMigrationsFrom()` in DropzoneServiceProvider (line 42)
- Laravel automatically detects and runs package migrations
- Package migrations are namespaced and won't conflict with your app migrations

**Migration cleanup (optional):**
```bash
# List Dropzone Enhanced migrations in your project
ls database/migrations/*_create_photos_table.php
ls database/migrations/*_add_locale_to_photos_table.php
ls database/migrations/*_add_user_id_to_photos_table.php

# Safe to delete - package will provide them automatically
```

## 2.2.1 - 2025-12-18

### Fixed
- Guarded the `photos` table creation migration to skip when the table already exists (safe for existing installs).
- Guarded the locale migration to only add/remove the column and index when applicable.
- Renamed the `create_photos_table` migration to include a timestamp so fresh installs run it before locale changes.

### Upgrade Notes
- After updating, run `php artisan migrate` as usual. Existing installations will record the renamed migration without attempting to recreate the table.

## 2.2.0 - 2025-12-18

### 🌍 Multilingual Photo Support

A major new feature enabling locale-specific photo management for multilingual applications. Perfect for sites where images contain text in different languages.

#### ⚠️ UPGRADE INSTRUCTIONS

**For existing installations:**
```bash
# 1. Update the package
composer update maccesar/laravel-dropzone-enhanced

# 2. Run migration (REQUIRED)
php artisan migrate

# 3. Enable in config/dropzone.php (optional)
# Set 'multilingual.enabled' => true
```

**The migration adds a nullable `locale` column - 100% backward compatible. Existing photos will continue to work without any changes.**

#### Added

**Database:**
- New `locale` column in `photos` table (nullable for backward compatibility)
- Composite index `(photoable_type, photoable_id, locale)` for efficient queries
- Migration: `2025_12_18_000001_add_locale_to_photos_table.php`

**Configuration:**
- New `multilingual` section in `config/dropzone.php`:
  - `enabled` - Enable/disable multilingual support (default: false)
  - When enabled, accepts any locale string - no need to pre-configure languages

**Photo Model:**
- `locale` field added to `$fillable`
- `scopeForLocale($locale)` - Filter photos by specific locale
- `static groupByLocale($photoableType, $photoableId)` - Get photos grouped by locale

**HasPhotos Trait:**
- `photosByLocale(?string $locale)` - Get photos for specific locale
- `photosGroupedByLocale()` - Get all photos grouped by locale
- `hasPhotosForLocale(?string $locale)` - Check if photos exist for locale
- `deletePhotosForLocale(?string $locale)` - Delete all photos for locale
- `mainPhoto(?string $locale)` - Now accepts optional locale parameter
- `setMainPhoto($photoId)` - Only affects photos in same locale

**Controller:**
- `DropzoneController::upload()` - Accepts optional `locale` parameter (any string, max 10 chars)
- Sort order calculated per locale (each locale has independent ordering)
- First photo uploaded for a locale automatically becomes main for that locale
- `DropzoneController::setMain()` - Only affects photos in same locale

**Blade Components:**
- `<x-dropzone-enhanced::area>` - New `locale` prop (optional)
  - Unique IDs per locale: `dropzone-container-{locale}`, `dropzone-upload-{locale}`
  - Automatically passes locale to upload endpoint
- `<x-dropzone-enhanced::photos>` - New `locale` prop (optional)
  - Automatically filters photos by locale
  - Unique container ID per locale

#### Usage Examples

**Basic Setup (Blade Components):**
```blade
{{-- Spanish Images --}}
<x-dropzone-enhanced::area
    :model="$content"
    directory="content"
    locale="es"
    :maxFiles="10"
/>
<x-dropzone-enhanced::photos :model="$content" locale="es" />

{{-- English Images --}}
<x-dropzone-enhanced::area
    :model="$content"
    directory="content"
    locale="en"
    :maxFiles="10"
/>
<x-dropzone-enhanced::photos :model="$content" locale="en" />
```

**Backend Usage:**
```php
// Get photos for specific locale
$spanishPhotos = $content->photosByLocale('es');
$englishPhotos = $content->photosByLocale('en');

// Get main photo for locale
$mainPhoto = $content->mainPhoto('es');

// Check if locale has photos
if ($content->hasPhotosForLocale('en')) {
    // Has English photos
}

// Get with fallback
$photos = $content->photosByLocaleWithFallback('es');

// Get all grouped
$grouped = $content->photosGroupedByLocale();
// Returns: ['en' => [...], 'es' => [...]]
```

**Frontend Views:**
```blade
{{-- Current app locale --}}
<img src="{{ $content->mainPhoto()->getUrl('800x600') }}">

{{-- Specific locale --}}
<img src="{{ $content->mainPhoto('es')->getUrl('800x600') }}">

{{-- Loop photos for locale --}}
@foreach($content->photosByLocale('en') as $photo)
    <img src="{{ $photo->getUrl('400x300') }}">
@endforeach
```

#### Features

- ✅ **100% Backward Compatible** - Existing photos work unchanged (locale = null)
- ✅ **Opt-in** - Disabled by default, activate with `locale` prop
- ✅ **Independent Main Photos** - Each locale has its own main photo
- ✅ **Independent Ordering** - Sort order maintained per locale
- ✅ **Flexible Fallback** - Multiple strategies for missing photos
- ✅ **Auto-scoping** - Optionally filter by current app locale automatically
- ✅ **Clean API** - Intuitive methods following Laravel conventions

#### Migration Notes

- **Existing installations**: Migration adds nullable `locale` column - no data changes required
- **New installations**: Work exactly as before when multilingual is disabled
- **Gradual adoption**: Can enable locale support per module/model
- **No breaking changes**: All existing code continues working

## 2.1.13 - 2025-12-17

### Added
- `--no-interaction` flag support in `dropzoneenhanced:install` command for automated/scripted installations
- Automatic migration execution in non-interactive mode (no user prompt)

### Changed
- Install command now properly handles both interactive and non-interactive modes
- Non-interactive installations run migrations automatically (perfect for CI/CD and package auto-installers)

## 2.1.12 - 2025-11-30

### Added
- Configurable crop alignment (`crop_position`) for generated thumbnails across helpers and controller uploads.
- Supported positions: center (default), top/bottom/left/right, and corners (e.g., `top-left`), with canonicalized paths and cache keys.

## 2.1.11 - 2025-11-28

### ✨ Helpers and Aspect-Ratio Support

#### Added
- `src()` and `srcset()` helpers on `Photo` to generate optimized URLs and srcsets directly from the model
- `src()` and `srcset()` helpers on `HasPhotos` to get the main photo (fallback to first photo) without chaining
- `srcFromPath()` and `srcsetFromPath()` on `HasPhotos` to process any storage path (e.g., `clients/avatar/main-photo.jpg`) through `ImageProcessor`
- `keepOriginalName` option on `<x-dropzone-enhanced::area />` to store files with sanitized original filenames (adds suffix on collisions)

#### Changed
- `Photo::getThumbnailUrl()` now accepts width-only dimensions; height is inferred from the original aspect ratio
- Trait helpers and path helpers also accept width-only dimensions with automatic height inference

#### Notes
- Helpers respect existing config: `dropzone.storage.disk`, `dropzone.images.thumbnails.*`, and `use_relative_urls`
- If no main photo is marked, helpers still return the first available photo

## 2.1.10 - 2025-11-22

### 🔄 Main Photo Fallback Enhancement

#### Added
- **Automatic fallback for mainPhoto()**: When no photo has `is_main = true`, returns the first photo by `sort_order`
- **Auto-assign on unmark**: When unmarking a main photo, automatically assigns the next available photo as main

#### Fixed
- **Empty main photo issue**: `mainPhoto()` no longer returns `null` when photos exist but none is marked as main
- **UI consistency**: Ensures there's always a main photo when photos exist

#### Technical Details
```php
// HasPhotos trait - mainPhoto() now with fallback
public function mainPhoto()
{
    return $this->photos->where('is_main', true)->first()
        ?? $this->photos->first();
}

// DropzoneController - setMain() auto-assigns next photo
if ($isMain) {
    $photo->update(['is_main' => false]);

    // Fallback: set first available photo as main
    $firstPhoto = Photo::where('photoable_id', $photo->photoable_id)
        ->where('photoable_type', $photo->photoable_type)
        ->where('id', '!=', $photo->id)
        ->orderBy('sort_order', 'asc')
        ->first();

    if ($firstPhoto) {
        $firstPhoto->update(['is_main' => true]);
    }
}
```

#### Benefits
- ✅ **Consistent behavior**: `mainPhoto()` always returns a photo when photos exist
- ✅ **Better UX**: Users always see a main photo without manual intervention
- ✅ **API response**: `setMain()` now returns `new_main_id` for frontend updates
- 🎯 **Backward compatible**: No breaking changes to existing functionality

## 2.1.9 - 2025-10-31

### 🎨 UI Enhancement

#### Fixed
- **Upload preview display**: Changed Dropzone `thumbnailMethod` from "contain" to "crop"
- **Inconsistent preview sizing**: Horizontal images no longer leave empty space in preview thumbnails
- **Professional appearance**: All upload previews now fill the complete square area consistently

#### Technical Details
```javascript
// Before
thumbnailMethod: "contain", // Left empty space for non-square images

// After
thumbnailMethod: "crop", // Fills entire preview area, centered and cropped
```

#### Benefits
- ✅ **Consistent previews**: All thumbnails fill the square preview area completely
- ✅ **Better UX**: Professional Instagram-style preview thumbnails
- ✅ **Centered cropping**: Images are automatically centered before cropping
- 🎯 **Non-breaking**: Only affects upload preview display, not stored images or gallery thumbnails

#### Important Notes
- This change only affects the visual preview during file upload
- Original uploaded images remain completely unaffected
- Gallery thumbnails generated by ImageProcessor are not affected
- No configuration changes needed

## 2.1.8 - 2025-10-31

### 🔧 URL Generation Enhancement

#### Added
- **Relative URL support**: New `use_relative_urls` configuration option in `config/dropzone.php`
- **Smart URL conversion**: Automatic conversion from absolute to relative URLs when enabled
- **Environment-agnostic URLs**: URLs now work consistently across all environments without manual configuration

#### Fixed
- **APP_URL dependency issue**: Resolved problem where `APP_URL` in `.env` forced absolute URLs with domain
- **Local development URLs**: Fixed `http://localhost:8000/storage/...` appearing in generated URLs
- **Production inconsistency**: Eliminated need to comment out `APP_URL` for proper URL generation

#### Technical Improvements
```php
// Enhanced buildUrl() method in Photo model
private function buildUrl($path)
{
    $url = Storage::disk($this->disk)->url($path);

    // Check if we should use relative URLs (from config or default behavior)
    $useRelativeUrls = config('dropzone.images.use_relative_urls', true);

    if ($useRelativeUrls && str_starts_with($url, 'http')) {
        // Convert absolute URL to relative by removing the domain
        $parsedUrl = parse_url($url);
        $url = ($parsedUrl['path'] ?? '') . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
    }

    return $url;
}
```

#### Configuration
```php
// config/dropzone.php
'images' => [
    // ...
    // Use relative URLs (e.g., /storage/...) instead of absolute URLs
    // This prevents issues with APP_URL in .env
    'use_relative_urls' => true,
],
```

#### Benefits
- ✅ **No .env modifications needed**: Keep `APP_URL` configured without affecting image URLs
- ✅ **Better portability**: Same code works in local, staging, and production environments
- ✅ **Cleaner URLs**: Relative paths are lighter and more efficient
- ✅ **Flexible configuration**: Easy toggle between relative and absolute URLs if needed
- 🎯 **Backward compatible**: Works with existing installations without breaking changes

#### Migration Notes
- **New installations**: Relative URLs available but disabled by default (opt-in feature)
- **Existing installations**: Backward compatible - continues using absolute URLs unless you enable the feature
- **To enable relative URLs**: Republish config and set `use_relative_urls` to `true`:
  ```bash
  php artisan vendor:publish --tag=dropzoneenhanced-config --force
  php artisan config:clear
  ```
- **No breaking changes**: Package defaults to absolute URLs if config key is not present

## 2.1.7 - 2025-09-03

### 🎨 Image Quality & Preview Enhancement

#### Enhanced
- **Improved frontend image processing**: Changed `resizeMethod` from "contain" to "crop" then back to "contain" with optimized settings
- **Maximum quality processing**: Set `resizeQuality` to use config value (default 100%) for better image quality
- **Conditional resize processing**: Added proper `@if ($preResize)` conditional to respect the pre_resize configuration
- **Enhanced preview thumbnails**: Increased `thumbnailWidth/Height` from 200x200 to 576x576 for sharper preview quality
- **Better thumbnail method**: Set `thumbnailMethod: "contain"` to maintain aspect ratios in previews

#### Fixed  
- **Poor image quality issue**: Resolved pixelated/blocky image quality caused by low resize quality settings
- **Missing pre_resize condition**: Fixed frontend resize options always being active regardless of config setting
- **Blurry preview images**: Improved preview resolution during upload process

#### Technical Improvements
```javascript
// Enhanced Dropzone configuration
@if ($preResize)
  resizeMethod: "contain",
  resizeQuality: {{ config('dropzone.images.quality', 100) / 100 }},
  resizeWidth: {{ $dimensions ? explode('x', $dimensions)[0] : 1920 }},
  resizeHeight: {{ $dimensions ? explode('x', $dimensions)[1] : 1080 }},
@endif
thumbnailWidth: 576,
thumbnailHeight: 576,
thumbnailMethod: "contain",
```

#### Benefits
- ✅ **Better image quality**: Maximum quality processing with 100% default setting
- ✅ **Sharper previews**: Larger thumbnail dimensions for clearer upload previews  
- ✅ **Proper configuration respect**: `pre_resize: false` now actually disables frontend processing
- ✅ **Maintained performance**: Smart conditional processing based on user preference
- 🎯 **User control**: Full flexibility between quality vs file size via `pre_resize` setting

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
