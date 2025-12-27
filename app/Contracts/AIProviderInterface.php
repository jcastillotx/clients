<?php

namespace App\Contracts;

interface AIProviderInterface
{
    /**
     * Send a chat completion request.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * Stream chat responses.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @param  array<string, mixed>  $options
     * @return \Generator<int, array<string, mixed>, mixed, void>
     */
    public function streamChat(array $messages, array $options = []): \Generator;

    /**
     * Simple text generation.
     *
     * @param  array<string, mixed>  $options
     */
    public function generateText(string $prompt, array $options = []): string;

    /**
     * Document analysis.
     *
     * @param  array<string, mixed>  $instructions
     * @return array<string, mixed>
     */
    public function analyzeDocument(string $content, array $instructions = []): array;

    /**
     * Generate embeddings (for semantic search).
     *
     * @return array<int, float>
     */
    public function generateEmbeddings(string $text): array;

    /**
     * Estimate cost based on tokens.
     *
     * @param  array{input?:int, output?:int, total?:int}  $tokens
     */
    public function estimateCost(array $tokens): float;

    /**
     * List available models.
     *
     * @return array<int, string>
     */
    public function getModelList(): array;

    /**
     * Validate API key / connectivity.
     */
    public function validateApiKey(): bool;
}

