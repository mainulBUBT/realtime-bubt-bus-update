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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash')->unique();
            $table->json('fingerprint_data');
            $table->float('reputation_score')->default(0.5);
            $table->float('trust_score')->default(0.5);
            $table->integer('total_contributions')->default(0);
            $table->integer('accurate_contributions')->default(0);
            $table->float('clustering_score')->default(0.5);
            $table->float('movement_consistency')->default(0.5);
            $table->timestamp('last_activity')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamps();
            
            // Indexes for performance optimization
            $table->index('token_hash');
            $table->index('reputation_score');
            $table->index('trust_score');
            $table->index(['is_trusted', 'trust_score']);
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
