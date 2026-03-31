<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use a safer approach with a temporary column
        // Step 1: Add a new temporary column
        Schema::table('routes', function (Blueprint $table) {
            $table->string('direction_temp')->nullable()->after('direction');
        });

        // Step 2: Copy and transform data to the temporary column
        DB::statement("UPDATE routes SET direction_temp = CASE
            WHEN direction = 'up' THEN 'outbound'
            WHEN direction = 'down' THEN 'inbound'
            ELSE 'outbound'
        END");

        // Step 3: Drop the old direction column
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('direction');
        });

        // Step 4: Rename the temporary column to direction with the new enum
        Schema::table('routes', function (Blueprint $table) {
            $table->renameColumn('direction_temp', 'direction');
        });

        // Step 5: Now set the proper enum type on databases that support it.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE routes MODIFY COLUMN direction ENUM('outbound', 'inbound') NOT NULL");
        }

        // Step 6: Add code field and indexes
        Schema::table('routes', function (Blueprint $table) {
            $table->string('code', 20)->after('name');
            $table->index('direction', 'idx_direction');
            $table->index('code');
        });

        // Seed codes for existing routes
        DB::statement("UPDATE routes SET code = CONCAT('R', id) WHERE code IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse with the same approach
        Schema::table('routes', function (Blueprint $table) {
            $table->string('direction_temp')->nullable()->after('direction');
        });

        DB::statement("UPDATE routes SET direction_temp = CASE
            WHEN direction = 'outbound' THEN 'up'
            WHEN direction = 'inbound' THEN 'down'
            ELSE 'up'
        END");

        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('direction');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->renameColumn('direction_temp', 'direction');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE routes MODIFY COLUMN direction ENUM('up', 'down') NOT NULL");
        }

        Schema::table('routes', function (Blueprint $table) {
            $table->dropIndex('idx_direction');
            $table->dropIndex('code');
            $table->dropColumn('code');
        });
    }
};
