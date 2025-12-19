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
        Schema::table('photos', function (Blueprint $table) {
            // Add locale column (nullable for backwards compatibility)
            $table->string('locale', 5)->nullable()->after('is_main');

            // Add composite index for efficient querying by model + locale
            $table->index(['photoable_type', 'photoable_id', 'locale'], 'photos_locale_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex('photos_locale_index');
            $table->dropColumn('locale');
        });
    }
};
