<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update any existing routes with old direction values
        DB::statement("UPDATE routes SET direction = 'outbound' WHERE direction = 'up'");
        DB::statement("UPDATE routes SET direction = 'inbound' WHERE direction = 'down'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old values
        DB::statement("UPDATE routes SET direction = 'up' WHERE direction = 'outbound'");
        DB::statement("UPDATE routes SET direction = 'down' WHERE direction = 'inbound'");
    }
};
