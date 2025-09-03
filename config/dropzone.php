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
    'thumbnails' => ['enabled' => true, 'dimensions' => '288x288'],
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
];
