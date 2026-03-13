<?php

namespace App\Jobs;

use App\Models\BusActiveUser;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupInactiveUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $timeout = SystemSetting::getValue('inactive_user_timeout', 120);
        
        // Remove inactive users
        $deleted = BusActiveUser::inactive($timeout)->delete();
        
        if ($deleted > 0) {
            Log::info("Cleaned up {$deleted} inactive users.");
        }
        
        // Future: Logic to detect end of trip and clear user_locations
    }
}
