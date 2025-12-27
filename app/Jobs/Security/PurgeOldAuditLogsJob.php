<?php

namespace App\Jobs\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeOldAuditLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $days = (int) config('security.audit_retention_days', 365);
        $cutoff = now()->subDays(max(1, $days));

        // Spatie activitylog default table/model
        try {
            \Spatie\Activitylog\Models\Activity::query()
                ->where('created_at', '<', $cutoff)
                ->delete();
        } catch (\Throwable) {
            // ignore if package/table not present
        }
    }
}

