<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;
use App\Models\AiProvider;
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
     * Get the configured default provider.
     */
    public function defaultProvider(): AIProviderInterface
    {
        return $this->provider((string) config('ai-providers.default_provider', 'openai'));
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
}

