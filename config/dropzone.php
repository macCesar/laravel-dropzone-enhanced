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
    'prefix' => 'admin',
    'middleware' => ['web', 'auth'],
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
    'max_filesize' => 5000, // in KB
    'thumbnails' => [
      'enabled' => true,
      'dimensions' => '288x288',
    ],
  ],
];
