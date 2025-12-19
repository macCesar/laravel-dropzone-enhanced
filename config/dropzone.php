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
  */
  'multilingual' => [
    // Enable multilingual photo support
    'enabled' => false,

    // Available locales for photo uploads
    'locales' => ['en', 'es'],

    // Default locale when none is specified
    'default_locale' => 'en',

    // Fallback strategy when photos for requested locale are not found:
    // 'default' - Show photos from default_locale
    // 'null' - Show only photos with exact locale match
    // 'any' - Show photos from any locale
    'fallback_strategy' => 'default',

    // Automatically scope photos() by current app locale
    'auto_scope_by_app_locale' => true,
  ],
];
