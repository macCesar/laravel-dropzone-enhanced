# Changelog

All notable changes to `laravel-dropzone-enhanced` will be documented in this file.

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

## 1.2.1 - 2025-04-12

### Added
- Enhanced security for photo deletion with ownership verification
- Added `userCanDeletePhoto` method to verify permissions before photo deletion

### Changed
- Improved authorization checks in the `destroy` method for better security

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
