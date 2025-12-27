<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    /**
     * Send a push notification to a user (best-effort).
     *
     * Payload schema expected by service worker:
     *  - title
     *  - body
     *  - url
     */
    public function notifyUser(User $user, array $payload, array $options = []): array
    {
        $subs = PushSubscription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        if ($subs->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'error' => 'No subscriptions'];
        }

        $publicKey = (string) config('pwa.vapid_public_key');
        $privateKey = (string) config('pwa.vapid_private_key');
        if ($publicKey === '' || $privateKey === '') {
            return ['sent' => 0, 'failed' => 0, 'error' => 'Missing VAPID keys'];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $options['subject'] ?? (config('app.url') ?: 'https://localhost'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $ttl = (int) ($options['ttl'] ?? config('pwa.default_ttl', 3600));
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['title' => 'Client Portal', 'body' => 'Notification', 'url' => '/dashboard'], JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        foreach ($subs as $s) {
            $webPush->queueNotification(
                new Subscription(
                    endpoint: $s->endpoint,
                    publicKey: $s->public_key,
                    authToken: $s->auth_token,
                    contentEncoding: $s->content_encoding ?: 'aesgcm',
                ),
                $json,
                ['TTL' => $ttl]
            );
        }

        $sent = 0;
        $failed = 0;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
                continue;
            }

            $failed++;
            // If subscription is gone, delete it
            $endpoint = (string) $report->getRequest()?->getUri();
            if ($endpoint !== '') {
                PushSubscription::query()->where('endpoint', $endpoint)->delete();
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}

