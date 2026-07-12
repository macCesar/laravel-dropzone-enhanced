<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests\Views;

use Illuminate\Support\Facades\Blade;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestCase;

class AssetsTest extends TestCase
{
  public function test_sortable_is_bundled_and_rendered_only_once(): void
  {
    $this->assertFileExists(__DIR__ . '/../../resources/assets/Sortable.min.js');

    $html = Blade::render(
      '<x-dropzone-enhanced::sortable-script /><x-dropzone-enhanced::sortable-script />'
    );

    $this->assertSame(1, substr_count($html, 'Sortable.min.js'));
  }
}
