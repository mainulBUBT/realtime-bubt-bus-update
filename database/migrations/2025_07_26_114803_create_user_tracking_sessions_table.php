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
        Schema::create('user_tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('device_token', 255);
            $table->string('device_token_hash', 255)->nullable();
            $table->string('bus_id', 10);
            $table->string('session_id', 255)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->float('trust_score_at_start')->default(0.5);
            $table->integer('locations_contributed')->default(0);
            $table->integer('valid_locations')->default(0);
            $table->float('average_accuracy')->nullable();
            $table->float('total_distance_covered')->default(0);
            $table->json('session_metadata')->nullable();
            $table->timestamps();
            
            // Indexes for session management
            $table->index(['bus_id', 'is_active', 'started_at']);
            $table->index('device_token');
            $table->index('device_token_hash');
            $table->index(['is_active', 'started_at']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tracking_sessions');
    }
};
