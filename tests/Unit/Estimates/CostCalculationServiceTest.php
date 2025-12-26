<?php

namespace Tests\Unit\Estimates;

use App\Models\Client;
use App\Models\Setting;
use App\Services\Estimates\CostCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_calculation_applies_tier_override_markup_and_contingency(): void
    {
        Setting::setValue('billing.rate_card', [
            'default' => 100,
            'tiers' => [
                'premium' => 80,
            ],
        ]);

        $client = Client::factory()->create(['tier' => 'premium']);

        $svc = app(CostCalculationService::class);
        $res = $svc->calculate(10, 100, $client, 0.20, 0.10);

        $this->assertSame(100.0, $res['base_rate']);
        $this->assertSame(80.0, $res['tier_rate']);
        $this->assertSame('premium', $res['tier']);

        $this->assertEqualsWithDelta(800.0, $res['subtotal'], 1e-9);
        $this->assertEqualsWithDelta(160.0, $res['markup'], 1e-9);
        $this->assertEqualsWithDelta(96.0, $res['contingency'], 1e-9);
        $this->assertEqualsWithDelta(1056.0, $res['total'], 1e-9);
    }

    public function test_contingency_pct_defaults_by_complexity(): void
    {
        $svc = app(CostCalculationService::class);

        $this->assertEqualsWithDelta(0.05, $svc->contingencyPctForComplexity(2), 1e-9);
        $this->assertEqualsWithDelta(0.10, $svc->contingencyPctForComplexity(5), 1e-9);
        $this->assertEqualsWithDelta(0.15, $svc->contingencyPctForComplexity(8), 1e-9);
        $this->assertEqualsWithDelta(0.20, $svc->contingencyPctForComplexity(10), 1e-9);
    }
}

