<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->decimal('distance_to_next_m', 10, 2)->nullable()->after('sequence');
            $table->text('geometry_to_next')->nullable()->after('distance_to_next_m');
        });
    }

    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropColumn(['distance_to_next_m', 'geometry_to_next']);
        });
    }
};
