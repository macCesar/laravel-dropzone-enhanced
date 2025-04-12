# Changelog

All notable changes to `laravel-dropzone-enhanced` will be documented in this file.

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
