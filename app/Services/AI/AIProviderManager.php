<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use RuntimeException;

class AIProviderManager
{
    /**
     * Resolve a provider implementation by key.
     *
     * @param  'openai'|'claude'|'openrouter'|'perplexity'|'asksage'  $provider
     */
    public function provider(string $provider): AIProviderInterface
    {
        return match ($provider) {
            'openai' => app(OpenAIService::class)->configure($this->resolveProviderConfig($provider)),
            'claude' => app(ClaudeService::class)->configure($this->resolveProviderConfig($provider)),
            'openrouter' => app(OpenRouterService::class)->configure($this->resolveProviderConfig($provider)),
            'perplexity' => app(PerplexityService::class)->configure($this->resolveProviderConfig($provider)),
            'asksage' => app(AskSageService::class)->configure($this->resolveProviderConfig($provider)),
            default => throw new RuntimeException("Unknown AI provider: {$provider}"),
        };
    }

    /**
     * Factory method alias (requested name).
     */
    public function getProvider(string $providerName): AIProviderInterface
    {
        return $this->provider($providerName);
    }

    /**
     * Get the configured default provider.
     */
    public function defaultProvider(): AIProviderInterface
    {
        return $this->provider((string) config('ai-providers.default_provider', 'openai'));
    }

    /**
     * Smart routing: pick provider+model based on task type + complexity.
     *
     * @param  'low'|'medium'|'high'  $complexity
     * @return array{provider:string, model:?string}
     */
    public function routeToOptimalProvider(string $taskType, string $complexity = 'low'): array
    {
        // Task defaults override complexity.
        $task = $this->resolveTaskTarget($taskType);
        $provider = $task['provider'];
        $model = $task['model'];

        if (!$model) {
            $map = (array) config('ai-providers.routing.complexity_models.' . $complexity, []);
            $model = isset($map[$provider]) && is_string($map[$provider]) ? $map[$provider] : null;
        }

        // If provider is currently unhealthy, pick next healthy fallback.
        if ($this->isUnhealthy($provider)) {
            foreach ($this->fallbackOrder() as $alt) {
                if ($alt === $provider) continue;
                if (!$this->isUnhealthy($alt)) {
                    $provider = $alt;
                    if (!$model) {
                        $map = (array) config('ai-providers.routing.complexity_models.' . $complexity, []);
                        $model = isset($map[$provider]) && is_string($map[$provider]) ? $map[$provider] : null;
                    }
                    break;
                }
            }
        }

        return ['provider' => $provider, 'model' => $model];
    }

    /**
     * Execute a provider call with fallback + health monitoring.
     *
     * @param callable(AIProviderInterface):array<string,mixed> $fn
     * @return array<string, mixed>
     */
    public function withFallback(string $preferredProvider, callable $fn, ?string $taskType = null): array
    {
        $order = $this->fallbackOrder();
        $providers = array_values(array_unique(array_merge([$preferredProvider], $order)));

        $last = null;
        foreach ($providers as $p) {
            if ($this->isUnhealthy($p)) {
                continue;
            }
            try {
                $res = $fn($this->provider($p));
                $this->markHealthy($p);
                return $res;
            } catch (\Throwable $e) {
                $last = $e;
                $this->markFailure($p, $e);
                Log::warning('AI provider failed; trying fallback', [
                    'provider' => $p,
                    'task_type' => $taskType,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        throw new RuntimeException('All AI providers failed.', 0, $last instanceof \Throwable ? $last : null);
    }

    /**
     * Resolve provider configuration from DB with env fallback.
     *
     * @return array<string, mixed>
     */
    public function resolveProviderConfig(string $provider): array
    {
        $base = (array) config("ai-providers.providers.{$provider}", []);

        /** @var AiProvider|null $row */
        $row = AiProvider::query()
            ->where('name', $provider)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->orderBy('priority_order')
            ->first();

        if (!$row) {
            return $base;
        }

        return array_merge($base, array_filter([
            'api_key' => $row->api_key,
            'api_base' => $row->api_endpoint,
            'default_model' => $row->model_name,
            'cost_per_1k_input_tokens' => $row->cost_per_1k_input_tokens,
            'cost_per_1k_output_tokens' => $row->cost_per_1k_output_tokens,
            'rate_limit_per_minute' => $row->rate_limit_per_minute,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Resolve provider+model for a specific task type, with fallback.
     *
     * @return array{provider:string, model:?string}
     */
    public function resolveTaskTarget(string $taskType): array
    {
        $cfg = (array) config("ai-providers.task_models.{$taskType}", []);
        $provider = (string) Arr::get($cfg, 'provider', config('ai-providers.default_provider', 'openai'));
        $model = Arr::get($cfg, 'model');
        return ['provider' => $provider, 'model' => is_string($model) ? $model : null];
    }

    /**
     * @return array<int, string>
     */
    protected function fallbackOrder(): array
    {
        return (array) config('ai-providers.fallback.order', ['openai', 'openrouter', 'claude', 'perplexity', 'asksage']);
    }

    protected function healthKey(string $provider): string
    {
        return "ai:provider_health:{$provider}";
    }

    protected function isUnhealthy(string $provider): bool
    {
        $state = (array) Cache::get($this->healthKey($provider), []);
        $until = (int) ($state['cooldown_until'] ?? 0);
        return $until > time();
    }

    protected function markFailure(string $provider, \Throwable $e): void
    {
        $key = $this->healthKey($provider);
        $state = (array) Cache::get($key, ['fails' => 0, 'cooldown_until' => 0]);
        $fails = (int) ($state['fails'] ?? 0) + 1;

        // After 3 failures, cool down for 2 minutes.
        $cooldown = $fails >= 3 ? (time() + 120) : 0;

        Cache::put($key, [
            'fails' => $fails,
            'cooldown_until' => $cooldown,
            'last_error' => substr($e->getMessage(), 0, 500),
        ], now()->addMinutes(10));
    }

    protected function markHealthy(string $provider): void
    {
        Cache::forget($this->healthKey($provider));
    }
}

