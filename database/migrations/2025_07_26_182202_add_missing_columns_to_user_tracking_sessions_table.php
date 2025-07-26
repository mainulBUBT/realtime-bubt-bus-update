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
        Schema::table('user_tracking_sessions', function (Blueprint $table) {
            // Add missing columns that were in the newer migration
            $table->string('session_id', 100)->unique()->after('bus_id');
            $table->string('device_token_hash', 255)->index()->after('device_token');
            $table->float('average_accuracy')->nullable()->after('valid_locations');
            $table->float('total_distance_covered')->default(0)->after('average_accuracy');
            $table->json('session_metadata')->nullable()->after('total_distance_covered');
            
            // Add additional indexes for performance
            $table->index(['started_at', 'ended_at'], 'idx_session_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tracking_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'session_id',
                'device_token_hash', 
                'average_accuracy',
                'total_distance_covered',
                'session_metadata'
            ]);
            $table->dropIndex('idx_session_duration');
        });
    }
};
