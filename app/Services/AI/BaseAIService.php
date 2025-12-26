<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;
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

