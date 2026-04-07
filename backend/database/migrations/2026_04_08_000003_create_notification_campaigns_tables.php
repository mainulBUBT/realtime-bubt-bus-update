<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove legacy per-user notifications table (old data).
        if (Schema::hasTable('notifications')) {
            Schema::drop('notifications');
        }

        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('audience')->index(); // all_students | selected_students
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('info'); // info | warning | alert
            $table->string('image_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_sent_at')->nullable()->index();
            $table->unsignedInteger('resend_count')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('notification_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('notification_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('notification_campaign_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('notification_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->index();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_campaign_reads');
        Schema::dropIfExists('notification_campaign_recipients');
        Schema::dropIfExists('notification_campaigns');

        // Legacy table intentionally not restored.
    }
};

