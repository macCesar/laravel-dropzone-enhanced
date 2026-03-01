<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Routes
  |--------------------------------------------------------------------------
  |
  | Routes configuration for controllers
  |
  */
  'routes' => [
    'prefix' => '',
    'middleware' => ['web'],
  ],

  /*
  |--------------------------------------------------------------------------
  | Images
  |--------------------------------------------------------------------------
  |
  | Configuration for image processing
  |
  */
  'images' => [
    'quality' => 100,
    'max_files' => 10,
    'pre_resize' => true,
    'max_filesize' => 10000, // in KB
    'default_dimensions' => '1920x1080',
    'thumbnails' => [
      'enabled' => true,
      'dimensions' => '288x288',
      'crop_position' => 'center', // options: center, top, bottom, left, right, top-left, top-right, bottom-left, bottom-right
    ],

    // Warm (pre-generate) thumbnail sizes immediately at upload time.
    // Supports width-only ('462') or WxH ('1200x675') — same syntax as src() / srcset().
    // Empty array (default) disables warm generation (current behavior unchanged).
    'warm_sizes'  => [],

    // Multiplier for warm generation. warm_factor=2 generates 1x and 2x for each size.
    // Matches the $multipliers argument of srcset(). Range: 1–5.
    'warm_factor' => 1,

    // Output format for warmed thumbnails.
    'warm_format' => 'webp',

    // Use relative URLs (e.g., /storage/...) instead of absolute URLs (e.g., http://localhost:8000/storage/...)
    // This prevents issues with APP_URL in .env and makes URLs work across different environments
    // Set to true to enable this feature (disabled by default for backward compatibility)
    'use_relative_urls' => false,
  ],

  /*
  |--------------------------------------------------------------------------
  | Storage
  |--------------------------------------------------------------------------
  |
  | Configuration for image storage
  |
  */
  'storage' => [
    'disk' => 'public',
    'directory' => 'images',

    // Central directory for all generated thumbnails (relative to disk root).
    // Keeping thumbnails here makes cleanup easy: just delete this one folder.
    // Example: storage/app/public/cache/products/16/462x700_webp/photo.webp
    'thumbnail_cache_path' => 'cache',
  ],

  /*
  |--------------------------------------------------------------------------
  | Security
  |--------------------------------------------------------------------------
  |
  | Security settings for authorization and access control
  |
  */
  'security' => [
    // Custom access key for API or JavaScript requests
    'access_key' => null,

    // If true, any authenticated user can delete photos (use with caution)
    'allow_all_authenticated_users' => false,
  ],

  /*
  |--------------------------------------------------------------------------
  | Multilingual Support
  |--------------------------------------------------------------------------
  |
  | Enable locale-specific photo management for multilingual applications.
  | When enabled, photos can be associated with specific locales, allowing
  | different images for each language (useful for images containing text).
  |
  | When enabled = true, you can pass any locale string to components.
  | When enabled = false, all photos are stored without locale (null).
  |
  */
  'multilingual' => [
    'enabled' => false,
  ],
];
