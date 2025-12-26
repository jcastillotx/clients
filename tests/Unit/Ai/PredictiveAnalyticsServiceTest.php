<?php

namespace Tests\Unit\Ai;

use App\Models\Client;
use App\Models\Payment;
use App\Services\AI\AIProviderManager;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictiveAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_revenue_returns_expected_shape(): void
    {
        $svc = new PredictiveAnalyticsService(app(AIProviderManager::class));

        // Seed some payment history
        $client = Client::factory()->active()->create();
        Payment::factory()->count(3)->create([
            'client_id' => $client->id,
            'status' => 'succeeded',
            'amount' => 100,
            'processed_at' => now()->subMonths(2),
        ]);

        $out = $svc->forecastRevenue(3);

        $this->assertSame(3, $out['timeframe_months']);
        $this->assertIsArray($out['series']);
        $this->assertIsArray($out['forecast']);
        $this->assertCount(3, $out['forecast']);
        $this->assertArrayHasKey('month', $out['forecast'][0]);
        $this->assertArrayHasKey('predicted', $out['forecast'][0]);
        $this->assertArrayHasKey('ci80_low', $out['forecast'][0]);
        $this->assertArrayHasKey('ci80_high', $out['forecast'][0]);
    }

    public function test_predict_client_churn_probability_is_between_0_and_1(): void
    {
        $svc = new PredictiveAnalyticsService(app(AIProviderManager::class));
        $client = Client::factory()->active()->create();

        $out = $svc->predictClientChurn($client);
        $p = (float) $out['churn_probability'];
        $this->assertGreaterThanOrEqual(0.0, $p);
        $this->assertLessThanOrEqual(1.0, $p);
        $this->assertContains($out['risk_level'], ['low', 'medium', 'high']);
    }

    public function test_generate_client_health_score_is_0_to_100(): void
    {
        $svc = new PredictiveAnalyticsService(app(AIProviderManager::class));
        $client = Client::factory()->active()->create();

        $out = $svc->generateClientHealthScore($client);
        $this->assertGreaterThanOrEqual(0, $out['score']);
        $this->assertLessThanOrEqual(100, $out['score']);
        $this->assertContains($out['risk_level'], ['low', 'medium', 'high']);
        $this->assertArrayHasKey('breakdown', $out);
    }
}

