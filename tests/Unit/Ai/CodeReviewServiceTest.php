<?php

namespace Tests\Unit\Ai;

use App\Services\AI\AISafetyService;
use App\Services\AI\CodeReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_code_falls_back_when_ai_unavailable(): void
    {
        $safety = \Mockery::mock(AISafetyService::class);
        $safety->shouldReceive('safeChat')->andThrow(new \RuntimeException('AI not configured'));

        $svc = new CodeReviewService($safety);
        $out = $svc->reviewCode([
            ['path' => 'foo.php', 'language' => 'php', 'content' => '<?php echo "hi";'],
        ]);

        $this->assertIsArray($out);
        $this->assertArrayHasKey('summary', $out);
        $this->assertArrayHasKey('findings', $out);
        $this->assertArrayHasKey('overall_recommendation', $out);
        $this->assertTrue((bool) ($out['_meta']['fallback'] ?? false));
    }

    public function test_review_code_detects_secret_like_patterns_in_fallback(): void
    {
        $safety = \Mockery::mock(AISafetyService::class);
        $safety->shouldReceive('safeChat')->andThrow(new \RuntimeException('AI not configured'));

        $svc = new CodeReviewService($safety);
        $out = $svc->reviewCode([
            ['path' => 'secrets.txt', 'content' => 'sk-abcdefghijklmnopqrstuvwxyz1234567890'],
        ]);

        $this->assertSame('block', $out['overall_recommendation']);
        $this->assertSame('critical', $out['risk_level']);
        $this->assertNotEmpty($out['findings']);
    }
}

