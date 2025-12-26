<?php

namespace Tests\Feature;

use App\Jobs\DeliverWebhookJob;
use App\Models\Client;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_job_signs_payload_and_logs_success(): void
    {
        $client = Client::factory()->create();

        $secret = 'supersecret';
        $endpoint = WebhookEndpoint::create([
            'client_id' => $client->id,
            'event_type' => 'request.created',
            'webhook_url' => 'https://example.com/webhook',
            'secret' => $secret,
            'is_active' => true,
            'format' => 'generic',
        ]);

        $captured = [
            'timestamp' => null,
            'signature' => null,
            'event' => null,
            'body' => null,
        ];

        Http::fake(function (\Illuminate\Http\Client\Request $req) use (&$captured) {
            $captured['timestamp'] = (string) (($req->header('X-Webhook-Timestamp')[0] ?? ''));
            $captured['signature'] = (string) (($req->header('X-Webhook-Signature')[0] ?? ''));
            $captured['event'] = (string) (($req->header('X-Webhook-Event')[0] ?? ''));
            $captured['body'] = (string) $req->body();

            return Http::response('ok', 200);
        });

        DeliverWebhookJob::dispatchSync($endpoint->id, 'request.created', ['id' => 123, 'title' => 'Hello']);

        $this->assertSame('request.created', $captured['event']);
        $this->assertNotEmpty($captured['timestamp']);
        $this->assertNotEmpty($captured['signature']);
        $this->assertNotEmpty($captured['body']);

        // Verify HMAC signature
        preg_match('/t=(\d+),v1=([a-f0-9]+)/', $captured['signature'], $m);
        $this->assertSame($captured['timestamp'], $m[1] ?? null);
        $expected = hash_hmac('sha256', $captured['timestamp'] . '.' . $captured['body'], $secret);
        $this->assertSame($expected, $m[2] ?? null);

        $this->assertDatabaseHas('webhook_delivery_logs', [
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => 'request.created',
            'succeeded' => 1,
        ]);
    }

    public function test_webhook_job_logs_failure(): void
    {
        $client = Client::factory()->create();

        $endpoint = WebhookEndpoint::create([
            'client_id' => $client->id,
            'event_type' => 'request.created',
            'webhook_url' => 'https://example.com/webhook',
            'secret' => 'secret',
            'is_active' => true,
            'format' => 'generic',
        ]);

        Http::fake(fn () => Http::response('nope', 500));

        try {
            DeliverWebhookJob::dispatchSync($endpoint->id, 'request.created', ['id' => 1]);
            $this->fail('Expected webhook delivery to throw.');
        } catch (\Throwable) {
            // expected
        }

        $this->assertTrue(
            WebhookDeliveryLog::query()
                ->where('webhook_endpoint_id', $endpoint->id)
                ->where('event_type', 'request.created')
                ->where('succeeded', false)
                ->exists()
        );
    }
}

