<?php

namespace Tests\Unit\Ai;

use App\Services\AI\AIProviderManager;
use App\Services\AI\AISafetyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AISafetyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_check_redacts_common_pii(): void
    {
        $svc = new AISafetyService(app(AIProviderManager::class));
        $out = $svc->privacyCheck([
            ['role' => 'user', 'content' => 'Email me at test@example.com or call (555) 123-4567. SSN 123-45-6789'],
        ]);

        $this->assertStringNotContainsString('test@example.com', $out['redacted_text']);
        $this->assertStringContainsString('[REDACTED_EMAIL]', $out['redacted_text']);
        $this->assertStringContainsString('[REDACTED_PHONE]', $out['redacted_text']);
        $this->assertStringContainsString('[REDACTED_SSN]', $out['redacted_text']);
        $this->assertGreaterThanOrEqual(1, $out['pii']['email']);
    }

    public function test_content_moderation_flags_secret_like_tokens(): void
    {
        $svc = new AISafetyService(app(AIProviderManager::class));
        $m = $svc->contentModeration('Here is your key: sk-abcdefghijklmnopqrstuvwxyz123456');
        $this->assertFalse($m['allowed']);
        $this->assertContains('potential_secret_leak', $m['flags']);
    }
}
