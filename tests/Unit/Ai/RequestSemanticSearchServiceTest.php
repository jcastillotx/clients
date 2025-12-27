<?php

namespace Tests\Unit\Ai;

use App\Services\AI\RequestSemanticSearchService;
use PHPUnit\Framework\TestCase;

class RequestSemanticSearchServiceTest extends TestCase
{
    public function test_cosine_similarity_orders_vectors_as_expected(): void
    {
        $svc = new RequestSemanticSearchService();

        $a = [1, 0, 0];
        $b = [1, 0, 0];
        $c = [0, 1, 0];

        $this->assertEqualsWithDelta(1.0, $svc->cosineSimilarity($a, $b), 1e-9);
        $this->assertEqualsWithDelta(0.0, $svc->cosineSimilarity($a, $c), 1e-9);
    }

    public function test_variance_stats_returns_median_ratio(): void
    {
        $svc = new RequestSemanticSearchService();

        $stats = $svc->varianceStats([
            ['estimated_hours' => 10.0, 'actual_hours' => 12.0], // 1.2
            ['estimated_hours' => 10.0, 'actual_hours' => 10.0], // 1.0
            ['estimated_hours' => 10.0, 'actual_hours' => 15.0], // 1.5
        ]);

        $this->assertSame(3, $stats['count']);
        $this->assertEqualsWithDelta(1.2, (float) $stats['median_ratio'], 1e-9);
    }
}

