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
            $table->string('device_token_hash', 255)->nullable()->after('device_token');
            $table->index('device_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tracking_sessions', function (Blueprint $table) {
            $table->dropIndex(['device_token_hash']);
            $table->dropColumn('device_token_hash');
        });
    }
};
