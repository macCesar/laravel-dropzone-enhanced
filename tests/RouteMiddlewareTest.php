<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests;

use MacCesar\LaravelDropzoneEnhanced\DropzoneServiceProvider;

class RouteMiddlewareTest extends TestCase
{
  public function test_booting_the_package_does_not_emit_response_bytes(): void
  {
    ob_start();

    try {
      (new DropzoneServiceProvider($this->app))->boot();
      $output = ob_get_contents();
    } finally {
      ob_end_clean();
    }

    $this->assertSame('', $output, 'Package bootstrap output corrupts file downloads and their Content-Length.');
  }

  public function test_dropzone_routes_require_authentication_by_default(): void
  {
    $route = $this->app['router']->getRoutes()->getByName('dropzone.upload');

    $this->assertNotNull($route);
    $this->assertSame(['web', 'auth', 'throttle:60,1', 'signed'], $route->gatherMiddleware());
  }
}
