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
    'max_files' => 10,
    'max_filesize' => 10000, // en KB
    'thumbnails' => [
      'enabled' => true,
      'dimensions' => '288x288',
    ],
  ],
];
