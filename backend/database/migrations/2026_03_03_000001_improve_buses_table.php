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
        Schema::table('buses', function (Blueprint $table) {
            // Add display_name field after plate_number
            $table->string('display_name', 100)->after('plate_number');

            // Add code field after display_name
            $table->string('code', 20)->after('display_name');

            // Add deleted_at for soft deletes
            $table->timestamp('deleted_at')->nullable()->after('status');

            // Add composite index for status filtering with soft deletes
            $table->index(['status', 'deleted_at'], 'idx_status');

            // Add index for code lookups
            $table->index('code');
        });

        // Seed existing data with default values
        DB::statement("UPDATE buses SET display_name = CONCAT('Route ', id), code = CONCAT('B', id) WHERE display_name IS NULL OR code IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->dropIndex('idx_status');
            $table->dropIndex('buses_code_index');
            $table->dropColumn(['display_name', 'code', 'deleted_at']);
        });
    }
};
