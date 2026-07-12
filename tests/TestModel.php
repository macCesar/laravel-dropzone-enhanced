<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests;

use Illuminate\Database\Eloquent\Model;
use MacCesar\LaravelDropzoneEnhanced\Traits\HasPhotos;

class TestModel extends Model
{
  use HasPhotos;

  protected $guarded = [];
}
