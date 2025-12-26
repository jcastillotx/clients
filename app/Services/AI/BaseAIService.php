<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiTask;
use App\Models\AiUsageTracking;
use Illuminate\Support\Facades\Log;
use RuntimeException;

abstract class BaseAIService implements AIProviderInterface
{
    /** @var array<string, mixed> */
    protected array $config = [];

    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): static
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return array{input:int, output:int}
     */
    protected function normalizeTokenCounts(array $tokens): array
    {
        $input = (int) ($tokens['input'] ?? 0);
        $output = (int) ($tokens['output'] ?? 0);

        $total = (int) ($tokens['total'] ?? 0);
        if ($total > 0 && $input === 0 && $output === 0) {
            $input = $total;
        }

        return ['input' => max(0, $input), 'output' => max(0, $output)];
    }

    public function estimateCost(array $tokens): float
    {
        $t = $this->normalizeTokenCounts($tokens);
        $in = (float) ($this->config['cost_per_1k_input_tokens'] ?? 0);
        $out = (float) ($this->config['cost_per_1k_output_tokens'] ?? 0);

        return (($t['input'] / 1000.0) * $in) + (($t['output'] / 1000.0) * $out);
    }

    /**
     * Persist + log a provider interaction (best-effort).
     *
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $result
     */
    protected function recordInteraction(array $meta, array $result): void
    {
        try {
            $provider = (string) ($result['provider'] ?? $meta['provider'] ?? '');
            $model = (string) ($result['model'] ?? $meta['model'] ?? '');
            $taskType = isset($meta['task_type']) ? (string) $meta['task_type'] : null;
            $clientId = isset($meta['client_id']) ? (int) $meta['client_id'] : null;
            $userId = isset($meta['user_id']) ? (int) $meta['user_id'] : null;

            $tokens = (array) ($result['tokens'] ?? []);
            $inputTokens = (int) ($tokens['input'] ?? 0);
            $outputTokens = (int) ($tokens['output'] ?? 0);
            $cost = isset($result['cost']) ? (float) $result['cost'] : (float) ($result['estimated_cost'] ?? 0);

            Log::info('AI interaction', [
                'provider' => $provider,
                'model' => $model,
                'task_type' => $taskType,
                'client_id' => $clientId,
                'user_id' => $userId,
                'tokens_input' => $inputTokens,
                'tokens_output' => $outputTokens,
                'cost' => $cost,
                'response_time_ms' => $result['response_time_ms'] ?? null,
            ]);

            // Usage tracking
            if ($provider !== '' && ($clientId !== null || $userId !== null)) {
                AiUsageTracking::create([
                    'client_id' => $clientId,
                    'user_id' => $userId,
                    'provider' => $provider,
                    'model' => $model ?: null,
                    'tokens_input' => max(0, $inputTokens),
                    'tokens_output' => max(0, $outputTokens),
                    'cost' => $cost,
                    'task_type' => $taskType,
                ]);
            }

            // Task persistence (optional)
            if (isset($meta['task_id']) && (int) $meta['task_id'] > 0) {
                /** @var AiTask|null $task */
                $task = AiTask::query()->find((int) $meta['task_id']);
                if ($task) {
                    $task->update([
                        'provider_used' => $provider ?: $task->provider_used,
                        'model_used' => $model ?: $task->model_used,
                        'tokens_used' => (int) ($tokens['total'] ?? ($inputTokens + $outputTokens)) ?: $task->tokens_used,
                        'cost' => $cost ?: $task->cost,
                    ]);
                }
            }

            // Conversation/message persistence (optional)
            if (isset($meta['ai_conversation_id']) && (int) $meta['ai_conversation_id'] > 0) {
                /** @var AiConversation|null $conv */
                $conv = AiConversation::query()->find((int) $meta['ai_conversation_id']);
                if ($conv) {
                    if (isset($meta['user_message']) && is_string($meta['user_message']) && $meta['user_message'] !== '') {
                        AiMessage::create([
                            'ai_conversation_id' => $conv->id,
                            'role' => 'user',
                            'content' => $meta['user_message'],
                            'provider_used' => $provider ?: null,
                            'model_used' => $model ?: null,
                            'tokens_used' => null,
                            'cost' => null,
                            'response_time_ms' => null,
                        ]);
                    }

                    $assistant = (string) ($result['text'] ?? '');
                    if ($assistant !== '') {
                        AiMessage::create([
                            'ai_conversation_id' => $conv->id,
                            'role' => 'assistant',
                            'content' => $assistant,
                            'provider_used' => $provider ?: null,
                            'model_used' => $model ?: null,
                            'tokens_used' => (int) ($tokens['total'] ?? ($inputTokens + $outputTokens)) ?: null,
                            'cost' => $cost ?: null,
                            'response_time_ms' => isset($result['response_time_ms']) ? (int) $result['response_time_ms'] : null,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Never break core flows due to logging/persistence.
            Log::warning('AI interaction logging failed: ' . $e->getMessage());
        }
    }

    public function analyzeDocument(string $content, array $instructions = []): array
    {
        $system = (string) ($instructions['system'] ?? 'Analyze the provided document. Return structured JSON.');
        $user = (string) ($instructions['user'] ?? 'Analyze this document:\n\n' . $content);

        return $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], $instructions);
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $res = $this->chat([['role' => 'user', 'content' => $prompt]], $options);
        $text = $res['text'] ?? $res['content'] ?? null;
        if (!is_string($text)) {
            throw new RuntimeException('Provider did not return text.');
        }
        return $text;
    }
}

