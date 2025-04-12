
<?php

use Illuminate\Support\Facades\Route;
use MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController;

Route::group([
  'prefix' => config('dropzone.routes.prefix', 'admin'),
  'middleware' => config('dropzone.routes.middleware', ['web', 'auth']),
], function () {
  // Dropzone routes for file upload and management
  Route::post('dropzone/upload', [DropzoneController::class, 'upload'])->name('dropzone.upload');
  Route::delete('dropzone/photos/{id}', [DropzoneController::class, 'destroy'])->name('dropzone.destroy');
  Route::post('dropzone/photos/{id}/main', [DropzoneController::class, 'setMain'])->name('dropzone.setMain');
  Route::get('dropzone/photos/{id}/is-main', [DropzoneController::class, 'checkIsMain'])->name('dropzone.checkIsMain');
  Route::post('dropzone/photos/reorder', [DropzoneController::class, 'reorder'])->name('dropzone.reorder');

  // Image serving route (used with Glide)
  Route::get('dropzone/image/{path}', [DropzoneController::class, 'serveImage'])
    ->where('path', '.*')
    ->name('dropzone.image');
});
