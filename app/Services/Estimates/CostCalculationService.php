<?php

namespace App\Services\Estimates;

use App\Models\Client;
use App\Models\Setting;

class CostCalculationService
{
    /**
     * @return array{
     *   base_rate:float,
     *   tier_rate:float,
     *   tier:string|null,
     *   markup_pct:float,
     *   contingency_pct:float,
     *   hours:float,
     *   subtotal:float,
     *   markup:float,
     *   contingency:float,
     *   total:float
     * }
     */
    public function calculate(float $hours, float $baseRate, ?Client $client = null, float $markupPct = 0.0, float $contingencyPct = 0.0): array
    {
        $hours = max(0.0, $hours);
        $baseRate = max(0.0, $baseRate);
        $markupPct = max(0.0, $markupPct);
        $contingencyPct = max(0.0, $contingencyPct);

        $tier = $client?->tier ?: null;
        $tierRate = $this->applyTierToRate($baseRate, $tier);

        $subtotal = $hours * $tierRate;
        $markup = $subtotal * $markupPct;
        $contingency = ($subtotal + $markup) * $contingencyPct;
        $total = $subtotal + $markup + $contingency;

        return [
            'base_rate' => $baseRate,
            'tier_rate' => $tierRate,
            'tier' => $tier,
            'markup_pct' => $markupPct,
            'contingency_pct' => $contingencyPct,
            'hours' => $hours,
            'subtotal' => $subtotal,
            'markup' => $markup,
            'contingency' => $contingency,
            'total' => $total,
        ];
    }

    public function defaultBaseRate(): float
    {
        $hourly = (float) Setting::getValue('billing.hourly_rate', 0);
        if ($hourly > 0) {
            return $hourly;
        }

        $card = Setting::getValue('billing.rate_card', null);
        if (is_array($card) && isset($card['default']) && is_numeric($card['default'])) {
            return (float) $card['default'];
        }

        return 100.0;
    }

    public function defaultMarkupPct(): float
    {
        $pct = Setting::getValue('billing.estimate_markup_pct', 0.0);
        if (is_numeric($pct)) {
            return max(0.0, (float) $pct);
        }

        return 0.2;
    }

    public function contingencyPctForComplexity(int $complexityScore): float
    {
        $complexityScore = max(1, min(10, $complexityScore));

        $mapping = Setting::getValue('billing.contingency_by_complexity', null);
        if (is_array($mapping)) {
            // Accept either ranges or per-score mapping.
            if (isset($mapping[$complexityScore]) && is_numeric($mapping[$complexityScore])) {
                return max(0.0, (float) $mapping[$complexityScore]);
            }
        }

        return match (true) {
            $complexityScore <= 2 => 0.05,
            $complexityScore <= 5 => 0.10,
            $complexityScore <= 8 => 0.15,
            default => 0.20,
        };
    }

    protected function applyTierToRate(float $baseRate, ?string $tier): float
    {
        $tier = $tier ? strtolower($tier) : null;
        if (! $tier) {
            return $baseRate;
        }

        $card = Setting::getValue('billing.rate_card', null);
        if (is_array($card)) {
            // Optional override: ['tiers' => ['premium' => 120]]
            if (isset($card['tiers'][$tier]) && is_numeric($card['tiers'][$tier])) {
                return (float) $card['tiers'][$tier];
            }
        }

        $multipliers = Setting::getValue('billing.tier_rate_multipliers', null);
        if (is_array($multipliers) && isset($multipliers[$tier]) && is_numeric($multipliers[$tier])) {
            return $baseRate * (float) $multipliers[$tier];
        }

        return $baseRate;
    }
}
