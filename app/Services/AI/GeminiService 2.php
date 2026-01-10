<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeminiService extends BaseAIService
{
    protected string $apiKey = '';
    protected string $apiBase = 'https://generativelanguage.googleapis.com/v1';

    /**
     * @param  array<string, mixed>  $config
     */
    public function configure(array $config): static
    {
        parent::configure($config);

        $this->apiKey = (string) ($this->config['api_key'] ?? '');
        $this->apiBase = (string) ($this->config['api_base'] ?? 'https://generativelanguage.googleapis.com/v1');

        return $this;
    }

    public function chat(array $messages, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'gemini-2.0-flash-exp');

        // Convert messages to Gemini format
        $contents = $this->convertMessagesToGeminiFormat($messages);

        $payload = [
            'contents' => $contents,
        ];

        // Add generation config if specified
        if (isset($options['temperature']) || isset($options['max_tokens']) || isset($options['top_p'])) {
            $payload['generationConfig'] = array_filter([
                'temperature' => $options['temperature'] ?? null,
                'maxOutputTokens' => $options['max_tokens'] ?? null,
                'topP' => $options['top_p'] ?? null,
            ]);
        }

        // Add safety settings for production use
        $payload['safetySettings'] = $options['safety_settings'] ?? [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
            ],
        ];

        $started = microtime(true);
        $response = $this->retry(function () use ($model, $payload) {
            return Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->apiBase}/models/{$model}:generateContent?key={$this->apiKey}", $payload);
        });
        $ms = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw new RuntimeException("Gemini API error: {$response->body()}");
        }

        $data = $response->json();
        $candidate = $data['candidates'][0] ?? null;
        $content = $candidate['content']['parts'][0]['text'] ?? '';
        $usage = $data['usageMetadata'] ?? null;

        $out = [
            'provider' => 'gemini',
            'model' => $model,
            'text' => (string) $content,
            'response_time_ms' => $ms,
            'tokens' => [
                'input' => (int) ($usage['promptTokenCount'] ?? 0),
                'output' => (int) ($usage['candidatesTokenCount'] ?? 0),
                'total' => (int) ($usage['totalTokenCount'] ?? 0),
            ],
        ];

        $out['estimated_cost'] = $this->estimateCostForModel($model, $out['tokens']);

        // Record interaction
        $this->recordInteraction([
            'provider' => 'gemini',
            'model' => $model,
            'task_type' => $options['task_type'] ?? null,
            'client_id' => $options['client_id'] ?? null,
            'user_id' => $options['user_id'] ?? null,
            'ai_conversation_id' => $options['ai_conversation_id'] ?? null,
            'task_id' => $options['task_id'] ?? null,
            'user_message' => $this->extractUserMessage($messages),
        ], $out);

        return $out;
    }

    /**
     * Analyze image with Gemini Vision.
     *
     * @param  string  $imagePath  Path to image file or base64 encoded data URI
     * @param  string  $prompt  Instructions for analysis
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function analyzeImage(string $imagePath, string $prompt, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $model = (string) ($options['model'] ?? $this->config['vision_model'] ?? 'gemini-2.0-flash-exp');

        // Prepare image data
        $imageData = $this->prepareImageData($imagePath);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $imageData['mime_type'],
                                'data' => $imageData['data'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Add generation config
        if (isset($options['temperature']) || isset($options['max_tokens'])) {
            $payload['generationConfig'] = array_filter([
                'temperature' => $options['temperature'] ?? null,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
            ]);
        }

        $started = microtime(true);
        $response = $this->retry(function () use ($model, $payload) {
            return Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->apiBase}/models/{$model}:generateContent?key={$this->apiKey}", $payload);
        });
        $ms = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw new RuntimeException("Gemini Vision API error: {$response->body()}");
        }

        $data = $response->json();
        $candidate = $data['candidates'][0] ?? null;
        $content = $candidate['content']['parts'][0]['text'] ?? '';
        $usage = $data['usageMetadata'] ?? null;

        $out = [
            'provider' => 'gemini',
            'model' => $model,
            'text' => (string) $content,
            'response_time_ms' => $ms,
            'tokens' => [
                'input' => (int) ($usage['promptTokenCount'] ?? 0),
                'output' => (int) ($usage['candidatesTokenCount'] ?? 0),
                'total' => (int) ($usage['totalTokenCount'] ?? 0),
            ],
        ];

        $out['estimated_cost'] = $this->estimateCostForModel($model, $out['tokens']);

        // Record interaction
        $this->recordInteraction([
            'provider' => 'gemini',
            'model' => $model,
            'task_type' => $options['task_type'] ?? 'image_analysis',
            'client_id' => $options['client_id'] ?? null,
            'user_id' => $options['user_id'] ?? null,
            'user_message' => "Image analysis: {$prompt}",
        ], $out);

        return $out;
    }

    /**
     * Generate brand style guidelines from logo/design assets.
     *
     * @param  array<string>  $imagePaths
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateBrandGuidelines(array $imagePaths, array $options = []): array
    {
        $model = (string) ($options['model'] ?? 'gemini-2.0-flash-exp');

        $parts = [
            [
                'text' => "Analyze these brand assets and generate comprehensive brand style guidelines. Include:\n\n"
                    ."1. Color Palette: Extract primary, secondary, and accent colors with hex codes\n"
                    ."2. Typography: Identify fonts or suggest similar alternatives\n"
                    ."3. Logo Usage: Guidelines for spacing, sizing, backgrounds\n"
                    ."4. Design Principles: Visual style, patterns, spacing rules\n"
                    ."5. Do's and Don'ts: Best practices and what to avoid\n"
                    ."6. Responsive Design: How elements adapt across screen sizes\n\n"
                    ."Format the output as structured JSON with sections for each guideline area.",
            ],
        ];

        // Add all images
        foreach ($imagePaths as $imagePath) {
            $imageData = $this->prepareImageData($imagePath);
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $imageData['mime_type'],
                    'data' => $imageData['data'],
                ],
            ];
        }

        $payload = [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.3,
                'maxOutputTokens' => $options['max_tokens'] ?? 4096,
            ],
        ];

        $started = microtime(true);
        $response = $this->retry(function () use ($model, $payload) {
            return Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(90)->post("{$this->apiBase}/models/{$model}:generateContent?key={$this->apiKey}", $payload);
        });
        $ms = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw new RuntimeException("Gemini API error: {$response->body()}");
        }

        $data = $response->json();
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $data['usageMetadata'] ?? null;

        return [
            'provider' => 'gemini',
            'model' => $model,
            'guidelines' => $content,
            'response_time_ms' => $ms,
            'tokens' => [
                'input' => (int) ($usage['promptTokenCount'] ?? 0),
                'output' => (int) ($usage['candidatesTokenCount'] ?? 0),
                'total' => (int) ($usage['totalTokenCount'] ?? 0),
            ],
            'estimated_cost' => $this->estimateCostForModel($model, [
                'input' => (int) ($usage['promptTokenCount'] ?? 0),
                'output' => (int) ($usage['candidatesTokenCount'] ?? 0),
            ]),
        ];
    }

    /**
     * Analyze frontend design mockup and generate implementation suggestions.
     *
     * @param  string  $mockupPath
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function analyzeFrontendDesign(string $mockupPath, array $options = []): array
    {
        $framework = $options['framework'] ?? 'Tailwind CSS + Livewire';
        $outputFormat = $options['output_format'] ?? 'component';

        $prompt = "Analyze this frontend design mockup and provide implementation guidance.\n\n"
            ."Framework: {$framework}\n\n"
            ."Please provide:\n"
            ."1. Layout Structure: HTML/component hierarchy\n"
            ."2. CSS/Tailwind Classes: Styling approach\n"
            ."3. Responsive Breakpoints: Mobile, tablet, desktop considerations\n"
            ."4. Component Breakdown: Reusable components identified\n"
            ."5. Accessibility Considerations: ARIA labels, keyboard navigation\n"
            ."6. Color Palette: Extract and list colors with hex codes\n"
            ."7. Typography: Font sizes, weights, line heights\n"
            ."8. Spacing: Margins, padding, gaps\n";

        if ($outputFormat === 'code') {
            $prompt .= "\n9. Sample Code: Provide starter code for the main component\n";
        }

        return $this->analyzeImage($mockupPath, $prompt, array_merge($options, [
            'task_type' => 'frontend_design_analysis',
        ]));
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        // Gemini doesn't have native PHP SDK streaming yet
        // For now, throw not implemented or do non-streaming fallback
        throw new RuntimeException('Streaming not yet implemented for Gemini');
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $result = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], $options);

        return $result['text'] ?? '';
    }

    public function analyzeDocument(string $content, array $instructions = []): array
    {
        $prompt = $instructions['prompt'] ?? 'Analyze this document and provide key insights.';

        $result = $this->chat([
            ['role' => 'user', 'content' => "{$prompt}\n\nDocument:\n{$content}"],
        ], $instructions);

        return [
            'analysis' => $result['text'],
            'tokens' => $result['tokens'],
            'cost' => $result['estimated_cost'],
        ];
    }

    public function generateEmbeddings(string $text): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $model = 'text-embedding-004';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$this->apiBase}/models/{$model}:embedContent?key={$this->apiKey}", [
            'model' => "models/{$model}",
            'content' => [
                'parts' => [
                    ['text' => $text],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Gemini Embeddings API error: {$response->body()}");
        }

        $data = $response->json();

        return $data['embedding']['values'] ?? [];
    }

    public function estimateCost(array $tokens): float
    {
        return $this->estimateCostForModel(
            $this->config['default_model'] ?? 'gemini-2.0-flash-exp',
            $tokens
        );
    }

    public function getModelList(): array
    {
        return [
            'gemini-2.0-flash-exp',
            'gemini-1.5-pro',
            'gemini-1.5-flash',
            'gemini-1.5-flash-8b',
        ];
    }

    public function validateApiKey(): bool
    {
        try {
            if (empty($this->apiKey)) {
                return false;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(10)->get("{$this->apiBase}/models?key={$this->apiKey}");

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Convert OpenAI-style messages to Gemini format.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array<int, array<string, mixed>>
     */
    protected function convertMessagesToGeminiFormat(array $messages): array
    {
        $contents = [];

        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];

            // Gemini uses 'user' and 'model' roles
            if ($role === 'assistant') {
                $role = 'model';
            } elseif ($role === 'system') {
                // System messages become user messages with instruction prefix
                $role = 'user';
                $content = "SYSTEM INSTRUCTION: {$content}";
            }

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $content],
                ],
            ];
        }

        return $contents;
    }

    /**
     * Prepare image data for API request.
     *
     * @param  string  $imagePath
     * @return array{mime_type: string, data: string}
     */
    protected function prepareImageData(string $imagePath): array
    {
        // Handle data URI
        if (str_starts_with($imagePath, 'data:')) {
            preg_match('/data:(.*?);base64,(.*)/', $imagePath, $matches);
            $mimeType = $matches[1] ?? 'image/png';
            $base64Data = $matches[2] ?? '';

            return [
                'mime_type' => $mimeType,
                'data' => $base64Data,
            ];
        }

        // Handle local file path
        if (file_exists($imagePath)) {
            $imageContent = file_get_contents($imagePath);
            $mimeType = mime_content_type($imagePath) ?: 'image/png';

            return [
                'mime_type' => $mimeType,
                'data' => base64_encode($imageContent),
            ];
        }

        // Handle storage path
        if (Storage::exists($imagePath)) {
            $imageContent = Storage::get($imagePath);
            $mimeType = Storage::mimeType($imagePath) ?: 'image/png';

            return [
                'mime_type' => $mimeType,
                'data' => base64_encode($imageContent),
            ];
        }

        // Handle URL
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $imageContent = file_get_contents($imagePath);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            return [
                'mime_type' => $mimeType,
                'data' => base64_encode($imageContent),
            ];
        }

        throw new RuntimeException("Unable to load image from: {$imagePath}");
    }
}
