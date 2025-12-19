<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    if (!Schema::hasTable('photos')) {
      Schema::create('photos', function (Blueprint $table) {
        $table->id();
        $table->morphs('photoable');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        $table->string('filename');
        $table->string('original_filename');
        $table->string('disk')->default('public');
        $table->string('directory');
        $table->string('extension');
        $table->string('mime_type');
        $table->unsignedBigInteger('size');
        $table->unsignedInteger('width');
        $table->unsignedInteger('height');
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_main')->default(false);
        $table->timestamps();
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('photos');
  }
};
