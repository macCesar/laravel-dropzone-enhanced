
<?php

use Illuminate\Support\Facades\Route;
use MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController;

Route::group([
  'prefix' => config('dropzone.routes.prefix', ''),
  'middleware' => config('dropzone.routes.middleware', ['web']),
], function () {
  // Dropzone routes for file upload and management
  Route::post('dropzone/upload', [DropzoneController::class, 'upload'])->name('dropzone.upload');
  Route::delete('dropzone/photos/{id}', [DropzoneController::class, 'destroy'])->name('dropzone.destroy');
  Route::post('dropzone/photos/{id}/main', [DropzoneController::class, 'setMain'])->name('dropzone.setMain');
  Route::get('dropzone/photos/{id}/is-main', [DropzoneController::class, 'checkIsMain'])->name('dropzone.checkIsMain');
  Route::post('dropzone/photos/reorder', [DropzoneController::class, 'reorder'])->name('dropzone.reorder');
  Route::post('dropzone/photos/update-locale', [DropzoneController::class, 'updateLocale'])->name('dropzone.updateLocale');
});
