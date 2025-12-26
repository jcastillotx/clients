<?php

namespace App\Services\AI;

use App\Services\AI\Prompts\MeetingPrompts;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MeetingAssistantService
{
    public function __construct(
        protected AIProviderManager $providers
    ) {
    }

    /**
     * Transcribe meeting audio (OpenAI Whisper) and summarize with action items.
     *
     * @return array<string,mixed>
     */
    public function transcribeAndSummarize(string $audioPath, array $options = []): array
    {
        $taskId = $options['task_id'] ?? null;
        $disk = (string) ($options['disk'] ?? 'attachments');

        $transcript = $this->transcribeWithOpenAI(Storage::disk($disk)->path($audioPath), $options);

        // Summarize transcript
        $system = MeetingPrompts::summarizeSystem();
        $user = MeetingPrompts::summarizeUser($transcript, (array) ($options['participants'] ?? []));
        $summary = $this->chatJson('summarize_meeting', $system, $user, $options);
        $summary['transcript'] = $transcript;

        return $summary;
    }

    /**
     * @return array<string,mixed>
     */
    public function generateAgenda(string $meetingPurpose, array $participants = [], array $options = []): array
    {
        $system = MeetingPrompts::agendaSystem();
        $user = MeetingPrompts::agendaUser($meetingPurpose, $participants);
        return $this->chatJson('generate_agenda', $system, $user, $options);
    }

    /**
     * Best-effort transcription via OpenAI `audio/transcriptions`.
     */
    protected function transcribeWithOpenAI(string $fullPath, array $options = []): string
    {
        $apiKey = (string) config('ai-providers.providers.openai.api_key');
        $base = (string) config('ai-providers.providers.openai.api_base', 'https://api.openai.com/v1');
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $model = (string) ($options['transcription_model'] ?? 'whisper-1');

        $http = new HttpClient([
            'base_uri' => rtrim($base, '/') . '/',
            'timeout' => (int) ($options['timeout'] ?? 180),
        ]);

        $multipart = [
            [
                'name' => 'model',
                'contents' => $model,
            ],
            [
                'name' => 'file',
                'contents' => fopen($fullPath, 'r'),
                'filename' => basename($fullPath),
            ],
            [
                'name' => 'response_format',
                'contents' => 'json',
            ],
        ];

        $resp = $http->post('audio/transcriptions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'multipart' => $multipart,
        ]);

        $json = json_decode((string) $resp->getBody(), true);
        if (is_array($json) && isset($json['text']) && is_string($json['text'])) {
            return $json['text'];
        }

        return '';
    }

    /**
     * @return array<string,mixed>
     */
    protected function chatJson(string $taskType, string $system, string $user, array $options): array
    {
        $preferred = (string) ($options['provider'] ?? 'openai');
        $model = $options['model'] ?? null;

        try {
            $res = $this->providers->withFallback($preferred, function ($provider) use ($system, $user, $taskType, $options, $model) {
                return $provider->chat([
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ], [
                    'task_type' => $taskType,
                    'timeout' => (int) ($options['timeout'] ?? 120),
                    'task_id' => $options['task_id'] ?? null,
                    'client_id' => $options['client_id'] ?? null,
                    'user_id' => $options['user_id'] ?? null,
                    'model' => $model,
                ]);
            }, $taskType);

            $data = $this->parseJsonFromText((string) ($res['text'] ?? ''));
            $data['_meta'] = [
                'provider' => $res['provider'] ?? null,
                'model' => $res['model'] ?? null,
                'tokens' => $res['tokens'] ?? null,
                'estimated_cost' => $res['estimated_cost'] ?? null,
            ];
            return $data;
        } catch (\Throwable $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'api key') || str_contains($msg, 'not configured')) {
                return ['error' => 'AI provider not configured.'];
            }
            Log::warning("Meeting assistant failed ({$taskType}): " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseJsonFromText(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];
        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) return $decoded;
        }
        return [];
    }
}

