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
    'middleware' => ['web', 'auth', 'throttle:60,1'],
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
  | Upload Preview Thumbnails
  |--------------------------------------------------------------------------
  |
  | Client-side Dropzone previews. The generated thumbnail is intentionally
  | larger than the visible box so previews stay crisp on retina displays.
  |
  */
  'previews' => [
    'display_width' => 180,
    'display_height' => 180,
    'thumbnail_width' => 576,
    'thumbnail_height' => 576,
    'thumbnail_method' => 'crop',
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
    // Explicit opt-out for public upload forms. This bypasses the Gate only
    // for upload; management operations always remain authorized.
    'allow_public_uploads' => false,

    // Private uploads and every management operation use this Gate. The host
    // defines it with: ($user, $action, $model, ?Photo $photo).
    'authorization_ability' => 'dropzone.manage-photos',

    // Maximum decoded dimensions accepted by the server-side image processor.
    'max_width' => 12000,
    'max_height' => 12000,
    'max_pixels' => 40000000,

    // Limit server-controlled warm thumbnail generation.
    'max_warm_sizes' => 10,
  ],

  /*
  |--------------------------------------------------------------------------
  | Database
  |--------------------------------------------------------------------------
  |
  | Configure the optional uploader association created by the package
  | migration. Set users_table to null when the host does not want a FK.
  |
  */
  'database' => [
    'user_id_type' => 'bigint', // bigint, uuid, or ulid
    'users_table' => null,
    'users_key' => 'id',
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
