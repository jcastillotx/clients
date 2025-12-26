<?php

namespace App\Services\AI;

use App\Models\Invoice;
use App\Services\AI\Prompts\CommunicationPrompts;
use Illuminate\Support\Facades\Log;

class CommunicationAssistantService
{
    public function __construct(protected AIProviderManager $providers)
    {
    }

    /**
     * @param array<string,mixed> $context
     * @return array{subject:string, body:string, short_bullets:array<int,string>, _meta?:array<string,mixed>}
     */
    public function draftEmail(array $context, string $purpose, string $tone = 'friendly', array $options = []): array
    {
        $system = CommunicationPrompts::draftEmailSystem();
        $user = CommunicationPrompts::draftEmailUser($context, $purpose, $tone);
        return $this->chatJson('draft_email', $system, $user, $options);
    }

    /**
     * Suggest 3 replies for a client message.
     *
     * @param array<string,mixed> $context
     * @param array<int, array{role:string, content:string}> $history
     * @return array{recommended_tone:string, replies:array<int,array{title:string,text:string}>, _meta?:array<string,mixed>}
     */
    public function draftResponse(string $clientMessage, array $context = [], array $history = [], array $options = []): array
    {
        $system = CommunicationPrompts::smartRepliesSystem();
        $user = CommunicationPrompts::smartRepliesUser($clientMessage, $context, $history);
        return $this->chatJson('smart_replies', $system, $user, $options);
    }

    /**
     * Improve grammar/style.
     *
     * @return array{improved_text:string, changes_summary:array<int,string>, _meta?:array<string,mixed>}
     */
    public function improveWriting(string $text, array $options = []): array
    {
        $system = CommunicationPrompts::improveWritingSystem();
        $user = CommunicationPrompts::improveWritingUser($text, $options['tone'] ?? null);
        return $this->chatJson('improve_writing', $system, $user, $options);
    }

    /**
     * @return array{sentiment:string, urgency:string, confidence:float|int, signals:array<int,string>, suggested_tone:string, _meta?:array<string,mixed>}
     */
    public function analyzeSentiment(string $message, array $options = []): array
    {
        $system = CommunicationPrompts::sentimentSystem();
        $user = $message;
        return $this->chatJson('analyze_sentiment', $system, $user, $options);
    }

    /**
     * @return array{intent:string, categories:array<int,string>, suggested_next_step:string, _meta?:array<string,mixed>}
     */
    public function detectIntent(string $message, array $options = []): array
    {
        $system = CommunicationPrompts::intentSystem();
        $user = $message;
        return $this->chatJson('detect_intent', $system, $user, $options);
    }

    /**
     * @return array{detected_language:string|null, translated_text:string, _meta?:array<string,mixed>}
     */
    public function translate(string $text, string $targetLanguage, array $options = []): array
    {
        $system = CommunicationPrompts::translateSystem();
        $user = CommunicationPrompts::translateUser($text, $targetLanguage);
        return $this->chatJson('translate', $system, $user, $options);
    }

    /**
     * Convenience: invoice reminder email draft.
     *
     * @return array{subject:string, body:string, short_bullets:array<int,string>, _meta?:array<string,mixed>}
     */
    public function draftInvoiceReminderEmail(Invoice $invoice, string $kind = 'due_soon', string $tone = 'friendly', array $options = []): array
    {
        $invoice->loadMissing('client');
        $context = [
            'type' => 'invoice_reminder',
            'kind' => $kind,
            'invoice_number' => $invoice->invoice_number,
            'amount' => (float) $invoice->amount,
            'due_date' => optional($invoice->due_date)->toDateString(),
            'client' => $invoice->client?->company_name,
            'pay_url' => method_exists($invoice, 'pay_url') ? $invoice->pay_url : null,
        ];

        return $this->draftEmail($context, 'invoice_reminder', $tone, $options);
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
                    'timeout' => (int) ($options['timeout'] ?? 90),
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
            Log::warning("Communication assistant failed ({$taskType}): " . $e->getMessage());
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

