<?php

namespace MacCesar\LaravelDropzoneEnhanced\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use MacCesar\LaravelDropzoneEnhanced\Http\Controllers\DropzoneController;
use MacCesar\LaravelDropzoneEnhanced\Models\Photo;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestCase;
use MacCesar\LaravelDropzoneEnhanced\Tests\TestModel;
use MacCesar\LaravelDropzoneEnhanced\Tests\User;

class DropzoneControllerTest extends TestCase
{
  protected User $owner;
  protected User $otherUser;
  protected TestModel $model;

  protected function setUp(): void
  {
    parent::setUp();

    Storage::fake('public');
    $this->owner = User::create(['name' => 'Owner']);
    $this->otherUser = User::create(['name' => 'Other']);
    $this->model = TestModel::create(['user_id' => $this->owner->id]);

    Gate::define('dropzone.manage-photos', function (User $user, string $action, TestModel $model, ?Photo $photo = null) {
      return $user->id === $model->user_id;
    });
  }

  public function test_upload_requires_a_valid_signed_context(): void
  {
    $url = $this->signedUploadUrl();
    $tamperedUrl = str_replace('images%2F1', 'images%2F2', $url);

    $this->actingAs($this->owner)
      ->post($tamperedUrl, ['file' => UploadedFile::fake()->image('photo.jpg')])
      ->assertForbidden();

    $this->assertDatabaseCount('photos', 0);
  }

  public function test_upload_uses_the_detected_mime_extension_and_authorized_model(): void
  {
    config()->set('dropzone.images.thumbnails.enabled', false);
    $jpeg = UploadedFile::fake()->image('source.jpg');
    $renamedJpeg = new UploadedFile($jpeg->getPathname(), 'payload.php', 'image/jpeg', null, true);
    $this->assertSame('image/jpeg', $renamedJpeg->getMimeType());

    $response = $this->actingAs($this->owner)
      ->withHeader('Accept', 'application/json')
      ->post($this->signedUploadUrl(), [
        'file' => $renamedJpeg,
        'keep_original_name' => true,
      ]);

    $this->assertSame(200, $response->status(), $response->getContent());
    $response->assertJsonPath('success', true);
    $photo = Photo::firstOrFail();
    $this->assertSame('payload.jpg', $photo->filename);
    $this->assertSame($this->model->getMorphClass(), $photo->photoable_type);
    Storage::disk('public')->assertExists('images/1/payload.jpg');
  }

  public function test_upload_is_forbidden_for_a_user_who_cannot_manage_the_model(): void
  {
    $this->actingAs($this->otherUser)
      ->post($this->signedUploadUrl(), ['file' => UploadedFile::fake()->image('photo.jpg')])
      ->assertForbidden();

    $this->assertDatabaseCount('photos', 0);
  }

  public function test_public_uploads_can_be_enabled_without_authentication(): void
  {
    config()->set('dropzone.security.allow_public_uploads', true);
    config()->set('dropzone.images.thumbnails.enabled', false);
    $url = $this->publicUploadUrl();

    $this->withHeader('Accept', 'application/json')
      ->post($url, ['file' => UploadedFile::fake()->image('visitor.jpg')])
      ->assertOk()
      ->assertJsonPath('success', true);

    $photo = Photo::firstOrFail();
    $this->assertNull($photo->user_id);
    $this->assertSame($this->model->id, $photo->photoable_id);
  }

  public function test_public_upload_opt_out_does_not_open_management_endpoints(): void
  {
    config()->set('dropzone.security.allow_public_uploads', true);
    $photo = $this->createPhoto($this->model);
    Route::delete('test/dropzone/photos/{id}', [DropzoneController::class, 'destroy'])
      ->name('test.dropzone.destroy');

    $this->deleteJson(route('test.dropzone.destroy', $photo))->assertForbidden();
    $this->assertDatabaseHas('photos', ['id' => $photo->id]);
  }

  public function test_mutating_another_users_photo_is_forbidden(): void
  {
    $photo = $this->createPhoto($this->model);

    $this->actingAs($this->otherUser)
      ->post(route('dropzone.setMain', $photo))
      ->assertForbidden();

    $this->actingAs($this->otherUser)
      ->delete(route('dropzone.destroy', $photo))
      ->assertForbidden();
  }

  public function test_reorder_rejects_photos_from_different_models(): void
  {
    $otherModel = TestModel::create(['user_id' => $this->owner->id]);
    $first = $this->createPhoto($this->model);
    $second = $this->createPhoto($otherModel);

    $this->actingAs($this->owner)->postJson(route('dropzone.reorder'), [
      'photos' => [
        ['id' => $first->id, 'order' => 1],
        ['id' => $second->id, 'order' => 2],
      ],
    ])->assertUnprocessable();
  }

  public function test_every_photo_endpoint_denies_an_unauthorized_user(): void
  {
    config()->set('dropzone.multilingual.enabled', true);
    $photo = $this->createPhoto($this->model);

    $this->actingAs($this->otherUser)
      ->getJson(route('dropzone.checkIsMain', $photo))
      ->assertForbidden();

    $this->actingAs($this->otherUser)->postJson(route('dropzone.reorder'), [
      'photos' => [['id' => $photo->id, 'order' => 1]],
    ])->assertForbidden();

    $this->actingAs($this->otherUser)->postJson(route('dropzone.updateLocale'), [
      'photo_id' => $photo->id,
      'locale' => 'es',
    ])->assertForbidden();
  }

  public function test_locale_updates_accept_ten_character_locale_codes(): void
  {
    config()->set('dropzone.multilingual.enabled', true);
    $photo = $this->createPhoto($this->model);

    $this->actingAs($this->owner)->postJson(route('dropzone.updateLocale'), [
      'photo_id' => $photo->id,
      'locale' => 'zh-Hant-TW',
    ])->assertOk()->assertJsonPath('new_locale', 'zh-Hant-TW');

    $this->assertDatabaseHas('photos', [
      'id' => $photo->id,
      'locale' => 'zh-Hant-TW',
    ]);
  }

  public function test_upload_enforces_the_server_side_photo_limit(): void
  {
    config()->set('dropzone.images.max_files', 1);
    $this->createPhoto($this->model);

    $this->actingAs($this->owner)
      ->withHeader('Accept', 'application/json')
      ->post($this->signedUploadUrl(), ['file' => UploadedFile::fake()->image('photo.jpg')])
      ->assertUnprocessable();

    $this->assertDatabaseCount('photos', 1);
  }

  public function test_upload_rolls_back_database_and_storage_when_warm_configuration_is_invalid(): void
  {
    config()->set('dropzone.images.thumbnails.enabled', false);
    config()->set('dropzone.images.warm_sizes', array_fill(0, 11, '100x100'));

    $response = $this->actingAs($this->owner)
      ->withHeader('Accept', 'application/json')
      ->post($this->signedUploadUrl(), ['file' => UploadedFile::fake()->image('photo.jpg')]);

    $response->assertInternalServerError()
      ->assertJsonMissingPath('data')
      ->assertJsonMissingPath('exception');
    $this->assertDatabaseCount('photos', 0);
    $this->assertSame([], Storage::disk('public')->allFiles());
  }

  private function signedUploadUrl(): string
  {
    return URL::signedRoute('dropzone.upload', [
      'model_type' => TestModel::class,
      'model_id' => $this->model->id,
      'directory' => 'images/' . $this->model->id,
    ]);
  }

  private function publicUploadUrl(): string
  {
    Route::post('test/dropzone/public-upload', [DropzoneController::class, 'upload'])
      ->middleware('signed')
      ->name('test.dropzone.public-upload');

    return URL::signedRoute('test.dropzone.public-upload', [
      'model_type' => TestModel::class,
      'model_id' => $this->model->id,
      'directory' => 'public/' . $this->model->id,
    ]);
  }

  private function createPhoto(TestModel $model): Photo
  {
    return Photo::create([
      'photoable_id' => $model->id,
      'photoable_type' => $model->getMorphClass(),
      'user_id' => $model->user_id,
      'filename' => uniqid('photo-', true) . '.jpg',
      'original_filename' => 'photo.jpg',
      'disk' => 'public',
      'directory' => 'images/' . $model->id,
      'extension' => 'jpg',
      'mime_type' => 'image/jpeg',
      'size' => 100,
      'width' => 10,
      'height' => 10,
      'sort_order' => 1,
      'is_main' => false,
    ]);
  }
}
