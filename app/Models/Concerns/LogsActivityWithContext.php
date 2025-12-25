<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Activity;

trait LogsActivityWithContext
{
    /**
     * Add request/user context to Spatie activity records.
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $user = auth()->user();

        // Capture user + client for easier querying (custom columns on activity_logs table)
        if (property_exists($activity, 'user_id')) {
            $activity->user_id = $user?->id;
        } else {
            $activity->setAttribute('user_id', $user?->id);
        }

        $clientId = $user?->client_id;
        if ($clientId === null && property_exists($this, 'client_id')) {
            $clientId = $this->client_id;
        }
        if ($this instanceof \App\Models\Client) {
            $clientId = $this->id;
        }

        $activity->setAttribute('client_id', $clientId);

        // Capture IP/user-agent when running in an HTTP request context
        try {
            $req = request();
            $activity->setAttribute('ip_address', $req->ip());
            $activity->setAttribute('user_agent', $req->userAgent());
        } catch (\Throwable $e) {
            // ignore (e.g. running from console/queue worker without request context)
        }
    }
}

