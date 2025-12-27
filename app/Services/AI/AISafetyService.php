<?php

namespace App\Services\AI;

use App\Models\AiComplianceLog;
use App\Models\AiReviewQueueItem;
use App\Models\AiTask;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class AISafetyService
{
    public function __construct(protected AIProviderManager $providers)
    {
    }

    /**
     * Scan + redact PII in messages before sending to providers.
     *
     * @param array<int, array{role:string, content:string}> $messages
     * @return array{messages:array<int,array{role:string,content:string}>, pii:array<string,int>, redacted_text:string}
     */
    public function privacyCheck(array $messages, array $options = []): array
    {
        $text = implode("\n\n", array_map(fn ($m) => (string) ($m['content'] ?? ''), $messages));
        $redacted = $this->redactText($text, $piiCounts);

        // Sandbox: avoid real data usage (best-effort).
        $sandbox = (bool) (Setting::getValue('ai.sandbox.enabled', false) ?? false);
        if ($sandbox) {
            $redacted = $this->redactText($redacted, $tmpCounts); // re-run to be aggressive
            $redacted .= "\n\n[SANDBOX MODE: data redacted]";
        }

        // Replace contents in the same order, roughly by splitting back.
        $chunks = preg_split("/\n{2,}/", $redacted) ?: [];
        $out = [];
        foreach ($messages as $idx => $m) {
            $out[] = [
                'role' => (string) ($m['role'] ?? 'user'),
                'content' => (string) ($chunks[$idx] ?? $m['content'] ?? ''),
            ];
        }

        return [
            'messages' => $out,
            'pii' => $piiCounts,
            'redacted_text' => $redacted,
        ];
    }

    /**
     * Moderation for AI outputs: disallow secrets, hate/profanity, and other unsafe content.
     *
     * @return array{allowed:bool, flags:array<int,string>}
     */
    public function contentModeration(string $text): array
    {
        $flags = [];
        $t = strtolower($text);

        // Obvious secret patterns
        $secretPatterns = [
            '/\bsk-[a-z0-9]{16,}\b/i', // OpenAI-style key
            '/\bAKIA[0-9A-Z]{16}\b/', // AWS access key id
            '/\bBearer\s+[A-Za-z0-9\-\._~\+\/]+=*\b/i',
        ];
        foreach ($secretPatterns as $re) {
            if (preg_match($re, $text)) {
                $flags[] = 'potential_secret_leak';
                break;
            }
        }

        // Basic profanity/hate guard (minimal list; configurable later)
        $badWords = (array) (Setting::getValue('ai.safety.blocklist_terms', [
            'slur', 'racial slur', 'kill yourself',
        ]) ?? []);
        foreach ($badWords as $w) {
            $w = strtolower(trim((string) $w));
            if ($w !== '' && str_contains($t, $w)) {
                $flags[] = 'inappropriate_language';
                break;
            }
        }

        // Professional tone checks (simple heuristic)
        if (preg_match('/\b(fuck|shit)\b/i', $text)) {
            $flags[] = 'unprofessional_tone';
        }

        return ['allowed' => empty($flags), 'flags' => array_values(array_unique($flags))];
    }

    /**
     * Fact check heuristic for research-like outputs: require sources when claims include numbers.
     *
     * @param array<string,mixed> $structured
     * @return array{passed:bool, flags:array<int,string>}
     */
    public function factCheck(array $structured): array
    {
        $flags = [];

        $sources = $structured['sources'] ?? null;
        $sourceCount = is_array($sources) ? count($sources) : 0;

        $body = json_encode($structured, JSON_UNESCAPED_SLASHES);
        $hasNumericClaims = is_string($body) && preg_match('/\b(\$|%|\d{1,3}(,\d{3})+|\d+\.\d+|\d+)\b/', $body);

        $minSources = (int) (Setting::getValue('ai.safety.min_sources', 3) ?? 3);
        if ($hasNumericClaims && $sourceCount < $minSources) {
            $flags[] = 'insufficient_citations';
        }

        return ['passed' => empty($flags), 'flags' => $flags];
    }

    /**
     * Validate JSON output has required keys; used for structured tasks.
     *
     * @param array<string,mixed> $structured
     * @param array<int,string> $requiredKeys
     * @return array{passed:bool, missing:array<int,string>}
     */
    public function validateStructured(array $structured, array $requiredKeys): array
    {
        $missing = [];
        foreach ($requiredKeys as $k) {
            if (!array_key_exists($k, $structured)) {
                $missing[] = (string) $k;
            }
        }
        return ['passed' => empty($missing), 'missing' => $missing];
    }

    /**
     * Automatic quality scoring (1-5) with simple flags.
     *
     * @return array{score:int, flags:array<int,string>}
     */
    public function qualityScore(string $query, string $output): array
    {
        $flags = [];
        $score = 3;

        $out = trim($output);
        if ($out === '' || $out === '(no response)') {
            return ['score' => 1, 'flags' => ['empty']];
        }
        if (mb_strlen($out) < 40) {
            $flags[] = 'too_short';
            $score -= 1;
        }

        // Relevance (keyword overlap)
        $qTokens = $this->tokens($query);
        $oTokens = $this->tokens($out);
        $overlap = count(array_intersect($qTokens, $oTokens));
        if (count($qTokens) >= 4 && $overlap <= 1) {
            $flags[] = 'low_relevance';
            $score -= 1;
        }

        // Basic grammar/punctuation heuristic
        if (preg_match('/[!?]{3,}/', $out)) {
            $flags[] = 'excessive_punctuation';
            $score -= 1;
        }

        $score = max(1, min(5, $score));
        return ['score' => $score, 'flags' => $flags];
    }

    /**
     * Safe chat wrapper: privacy redaction -> provider call -> moderation -> review queue.
     *
     * @param array<int,array{role:string,content:string}> $messages
     * @return array<string,mixed>
     */
    public function safeChat(array $messages, array $options = []): array
    {
        $taskType = (string) ($options['task_type'] ?? 'generic');
        $preferred = (string) ($options['provider'] ?? 'openai');
        $model = $options['model'] ?? null;

        $privacy = $this->privacyCheck($messages, $options);
        $piiDetected = array_sum($privacy['pii']) > 0;

        $rawInput = implode("\n\n", array_map(fn ($m) => (string) ($m['content'] ?? ''), $messages));
        $inputHash = hash('sha256', $rawInput);

        // Call provider
        $res = $this->providers->withFallback($preferred, function ($provider) use ($privacy, $options, $taskType, $model) {
            return $provider->chat($privacy['messages'], array_merge($options, [
                'task_type' => $taskType,
                'model' => $model,
            ]));
        }, $taskType);

        $text = (string) ($res['text'] ?? '');

        // Moderation
        $mod = $this->contentModeration($text);
        if (!$mod['allowed']) {
            $res['blocked'] = true;
            $res['safety_flags'] = $mod['flags'];
            $text = 'AI response blocked by safety policy. A human will review this.';
            $res['text'] = $text;
        }

        // Quality score
        $qs = $this->qualityScore((string) ($options['user_query'] ?? $rawInput), $text);
        $res['quality_score'] = $qs['score'];
        $res['quality_flags'] = $qs['flags'];

        // Persist compliance log (redacted input + output preview)
        $retentionDays = (int) (Setting::getValue('ai.compliance.retention_days', 30) ?? 30);
        $log = AiComplianceLog::create([
            'ai_task_id' => isset($options['task_id']) ? (int) $options['task_id'] : null,
            'ai_conversation_id' => $options['ai_conversation_id'] ?? null,
            'ai_message_id' => $options['ai_message_id'] ?? null,
            'client_id' => $options['client_id'] ?? null,
            'user_id' => $options['user_id'] ?? Auth::id(),
            'task_type' => $taskType,
            'provider' => $res['provider'] ?? $preferred,
            'model' => $res['model'] ?? (is_string($model) ? $model : null),
            'input_hash' => $inputHash,
            'input_redacted' => substr($privacy['redacted_text'], 0, 10000),
            'output_preview' => substr($text, 0, 3000),
            'pii_detected' => $piiDetected,
            'flagged_for_review' => false,
            'flags' => array_values(array_unique(array_merge($mod['flags'], $qs['flags']))),
            'retention_until' => now()->addDays(max(1, $retentionDays)),
            'deleted_at' => null,
        ]);

        // Review queue routing (policy)
        $review = $this->shouldRequireHumanReview($taskType, $res, $options);
        if ($review['required']) {
            $item = AiReviewQueueItem::create([
                'ai_task_id' => isset($options['task_id']) ? (int) $options['task_id'] : null,
                'ai_message_id' => $options['ai_message_id'] ?? null,
                'client_id' => $options['client_id'] ?? null,
                'created_by' => $options['user_id'] ?? Auth::id(),
                'category' => $review['category'],
                'status' => 'pending',
                'reason' => $review['reason'],
                'output_preview' => substr($text, 0, 10000),
            ]);
            $log->update(['flagged_for_review' => true]);
            $res['review_queue_id'] = $item->id;
        }

        // If task has an AiTask record, persist the auto score (do not overwrite manual ratings).
        if (isset($options['task_id']) && (int) $options['task_id'] > 0) {
            $task = AiTask::query()->find((int) $options['task_id']);
            if ($task && !$task->quality_rating) {
                $task->update(['quality_rating' => $qs['score']]);
            }
        }

        return $res;
    }

    /**
     * Provider comparison: run multiple providers and choose best by quality score.
     *
     * @param array<int,string> $providers
     * @param array<int,array{role:string,content:string}> $messages
     * @return array{best:array<string,mixed>, all:array<int,array<string,mixed>>}
     */
    public function compareProviders(array $providers, array $messages, array $options = []): array
    {
        $all = [];
        foreach ($providers as $p) {
            try {
                $r = $this->safeChat($messages, array_merge($options, ['provider' => $p]));
                $all[] = $r;
            } catch (\Throwable $e) {
                $all[] = ['provider' => $p, 'text' => 'Error: ' . $e->getMessage(), 'quality_score' => 1, 'blocked' => true];
            }
        }

        usort($all, function ($a, $b) {
            return ((int) ($b['quality_score'] ?? 0) <=> (int) ($a['quality_score'] ?? 0));
        });

        return ['best' => $all[0] ?? ['text' => 'No result'], 'all' => $all];
    }

    /**
     * @return array{required:bool, category:string, reason:string}
     */
    protected function shouldRequireHumanReview(string $taskType, array $result, array $options): array
    {
        $t = strtolower($taskType);

        // Legal/contractual content
        if (str_contains($t, 'contract') || $t === 'document_analysis') {
            return ['required' => true, 'category' => 'legal', 'reason' => 'Legal/contract-related AI output requires review.'];
        }

        // Financial estimates over threshold
        $threshold = (float) (Setting::getValue('ai.safety.financial_review_threshold_usd', 5000) ?? 5000);
        if ($threshold > 0 && (str_contains($t, 'estimate') || str_contains($t, 'invoice') || str_contains($t, 'pricing'))) {
            $cost = (float) ($options['estimated_project_cost_usd'] ?? 0);
            if ($cost >= $threshold) {
                return ['required' => true, 'category' => 'financial', 'reason' => 'Estimate exceeds configured review threshold.'];
            }
        }

        // Complaint/negative comms (heuristic)
        if (str_contains($t, 'draft') || str_contains($t, 'email') || str_contains($t, 'response')) {
            $txt = strtolower((string) ($result['text'] ?? ''));
            if (str_contains($txt, 'refund') || str_contains($txt, 'complaint') || str_contains($txt, 'apolog')) {
                return ['required' => true, 'category' => 'complaint', 'reason' => 'Potential complaint-sensitive communication.'];
            }
        }

        // Privacy flags or blocked content
        if (!empty($result['blocked']) || !empty($result['safety_flags'])) {
            return ['required' => true, 'category' => 'privacy', 'reason' => 'Safety policy flagged this output.'];
        }

        return ['required' => false, 'category' => 'other', 'reason' => ''];
    }

    protected function redactText(string $text, ?array &$counts = null): string
    {
        $counts = [
            'email' => 0,
            'phone' => 0,
            'credit_card' => 0,
            'ssn' => 0,
        ];

        $out = $text;

        // Emails
        $out = preg_replace_callback('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', function ($m) use (&$counts) {
            $counts['email']++;
            return '[REDACTED_EMAIL]';
        }, $out) ?? $out;

        // Phone numbers (basic)
        $out = preg_replace_callback('/\b(\+?\d{1,2}[\s\-\.])?(\(?\d{3}\)?[\s\-\.])\d{3}[\s\-\.]\d{4}\b/', function ($m) use (&$counts) {
            $counts['phone']++;
            return '[REDACTED_PHONE]';
        }, $out) ?? $out;

        // SSN
        $out = preg_replace_callback('/\b\d{3}-\d{2}-\d{4}\b/', function ($m) use (&$counts) {
            $counts['ssn']++;
            return '[REDACTED_SSN]';
        }, $out) ?? $out;

        // Credit cards (very rough + Luhn)
        $out = preg_replace_callback('/\b(?:\d[ -]*?){13,19}\b/', function ($m) use (&$counts) {
            $digits = preg_replace('/\D+/', '', $m[0]) ?? '';
            if ($digits !== '' && $this->luhnCheck($digits)) {
                $counts['credit_card']++;
                return '[REDACTED_CARD]';
            }
            return $m[0];
        }, $out) ?? $out;

        return $out;
    }

    protected function luhnCheck(string $digits): bool
    {
        $sum = 0;
        $alt = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) $n -= 9;
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10 === 0;
    }

    /**
     * @return array<int,string>
     */
    protected function tokens(string $text): array
    {
        $t = strtolower($text);
        $t = preg_replace('/[^a-z0-9\s]/', ' ', $t) ?? $t;
        $parts = array_values(array_filter(explode(' ', $t), fn ($x) => $x !== '' && strlen($x) >= 3));
        return array_values(array_unique(array_slice($parts, 0, 40)));
    }
}

