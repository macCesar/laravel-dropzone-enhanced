<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests;

use MacCesar\LaravelDropzoneEnhanced\DropzoneServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
  protected function getPackageProviders($app)
  {
    return [
      DropzoneServiceProvider::class,
    ];
  }
}
