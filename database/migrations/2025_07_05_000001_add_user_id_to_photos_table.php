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
        // Check if the user_id column already exists to avoid conflicts
        if (!Schema::hasColumn('photos', 'user_id')) {
            Schema::table('photos', function (Blueprint $table) {
                // Add user_id column as nullable after photoable_type
                $table->unsignedBigInteger('user_id')->nullable()->after('photoable_type');
                
                // Add index for better performance
                $table->index('user_id');
                
                // Optional: Add foreign key constraint (commented out for flexibility)
                // Uncomment if you want strict referential integrity:
                // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('photos', 'user_id')) {
            Schema::table('photos', function (Blueprint $table) {
                // Drop foreign key if it exists
                // $table->dropForeign(['user_id']);
                
                // Drop index
                $table->dropIndex(['user_id']);
                
                // Drop the column
                $table->dropColumn('user_id');
            });
        }
    }
};
