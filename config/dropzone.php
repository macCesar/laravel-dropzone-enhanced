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
    'default_dimensions' => '1920x1080',
    'pre_resize' => true,
    'quality' => 90,
    'max_files' => 10,
    'max_filesize' => 10000, // en KB
    'thumbnails' => [
      'enabled' => true,
      'dimensions' => '288x288',
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Storage
  |--------------------------------------------------------------------------
  |
  | Configuration for image storage
  | Falls back to Laravel Glide Enhanced disk configuration for compatibility
  |
  */
  'storage' => [
    'disk' => config('images.disk', 'public'),
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
    // If true, any authenticated user can delete photos (use with caution)
    'allow_all_authenticated_users' => false,

    // Custom access key for API or JavaScript requests
    'access_key' => null,
  ],
];
