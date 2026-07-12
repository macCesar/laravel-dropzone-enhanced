<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests;

class RouteMiddlewareTest extends TestCase
{
  public function test_dropzone_routes_require_authentication_by_default(): void
  {
    $route = $this->app['router']->getRoutes()->getByName('dropzone.upload');

    $this->assertNotNull($route);
    $this->assertSame(['web', 'auth', 'throttle:60,1', 'signed'], $route->gatherMiddleware());
  }
}
