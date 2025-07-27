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
        Schema::table('bus_locations', function (Blueprint $table) {
            // Add composite indexes for smart broadcasting queries
            $table->index(['bus_id', 'created_at', 'reputation_weight'], 'idx_bus_time_trust');
            $table->index(['bus_id', 'is_validated', 'created_at'], 'idx_bus_validated_time');
            $table->index(['created_at', 'is_validated'], 'idx_time_validated');
            $table->index(['device_token', 'created_at'], 'idx_device_time');
            
            // Add index for cleanup operations
            $table->index(['created_at'], 'idx_created_at_cleanup');
        });
        
        Schema::table('user_tracking_sessions', function (Blueprint $table) {
            // Add indexes for session management
            $table->index(['bus_id', 'is_active', 'started_at'], 'idx_bus_active_started');
            $table->index(['is_active', 'ended_at'], 'idx_active_ended');
            $table->index(['device_token', 'is_active'], 'idx_device_active');
        });
        
        Schema::table('device_tokens', function (Blueprint $table) {
            // Add indexes for trust score queries
            $table->index(['trust_score', 'is_trusted'], 'idx_trust_score_trusted');
            $table->index(['last_activity'], 'idx_last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bus_locations', function (Blueprint $table) {
            $table->dropIndex('idx_bus_time_trust');
            $table->dropIndex('idx_bus_validated_time');
            $table->dropIndex('idx_time_validated');
            $table->dropIndex('idx_device_time');
            $table->dropIndex('idx_created_at_cleanup');
        });
        
        Schema::table('user_tracking_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_bus_active_started');
            $table->dropIndex('idx_active_ended');
            $table->dropIndex('idx_device_active');
        });
        
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_trust_score_trusted');
            $table->dropIndex('idx_last_activity');
        });
    }
};
