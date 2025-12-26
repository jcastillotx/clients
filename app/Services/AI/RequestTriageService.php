<?php

namespace App\Services\AI;

use App\Models\ActivityLog;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use App\Services\AI\Prompts\RequestTriagePrompt;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestTriageService
{
    public function __construct(
        protected AIProviderManager $providers
    ) {
    }

    /**
     * Analyze a newly submitted request and return structured triage output.
     *
     * @return array<string, mixed>
     */
    public function analyzeNewRequest(ServiceRequest $request, array $options = []): array
    {
        $request->loadMissing(['client', 'creator', 'attachments']);

        $attachments = $this->attachmentMetadata($request);
        $similar = $this->suggestSimilarPastRequests($request);

        $messages = [
            ['role' => 'system', 'content' => RequestTriagePrompt::systemPrompt()],
            ['role' => 'user', 'content' => RequestTriagePrompt::userPrompt($request, $attachments, $similar)],
        ];

        // Prefer config default for triage; allow override via options.
        $target = $this->providers->routeToOptimalProvider('triage_request', (string) ($options['complexity'] ?? 'high'));
        $preferred = (string) ($options['provider'] ?? $target['provider'] ?? 'openai');
        $model = (string) ($options['model'] ?? $target['model'] ?? '');

        $res = $this->providers->withFallback($preferred, function ($provider) use ($messages, $request, $options, $model) {
            return $provider->chat($messages, [
                'model' => $model !== '' ? $model : null,
                'task_type' => 'triage_request',
                'client_id' => $request->client_id,
                'user_id' => $request->created_by,
                'timeout' => (int) ($options['timeout'] ?? 60),
            ]);
        }, 'triage_request');

        $triage = $this->parseJsonFromText((string) ($res['text'] ?? ''));
        $triage['_meta'] = [
            'provider' => $res['provider'] ?? $preferred,
            'model' => $res['model'] ?? $model,
            'tokens' => $res['tokens'] ?? null,
            'estimated_cost' => $res['estimated_cost'] ?? null,
        ];

        // Enrich with local (non-AI) additions:
        $triage['similar_requests'] = $similar;

        return $triage;
    }

    /**
     * Categorize into business categories and tag keywords.
     *
     * @return array{category:string, subcategory:string, keywords:array<int,string>}
     */
    public function categorizeRequest(ServiceRequest $request): array
    {
        $analysis = $this->analyzeNewRequest($request, ['complexity' => 'low']);

        return [
            'category' => (string) ($analysis['category'] ?? 'support'),
            'subcategory' => (string) ($analysis['subcategory'] ?? ''),
            'keywords' => array_values(array_filter(array_map('strval', (array) ($analysis['keywords'] ?? [])))),
        ];
    }

    /**
     * Suggest staff assignment (top 3) based on workload, relationship, and historical completions.
     *
     * @return array<int, array{user_id:int, name:string, score:float, reasoning:string}>
     */
    public function suggestAssignment(ServiceRequest $request): array
    {
        $clientId = (int) $request->client_id;

        $staff = User::query()
            ->whereNull('client_id')
            ->where('is_active', true)
            ->whereIn('status', ['active'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['staff', 'admin', 'super_admin']))
            ->get();

        $suggestions = [];

        foreach ($staff as $u) {
            $openAssigned = ServiceRequest::query()
                ->where('assigned_to', $u->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            $completed = ServiceRequest::query()
                ->where('assigned_to', $u->id)
                ->where('status', 'completed')
                ->count();

            $sameTypeCompleted = ServiceRequest::query()
                ->where('assigned_to', $u->id)
                ->where('status', 'completed')
                ->where('type', $request->type)
                ->count();

            $relationship = in_array($clientId, $u->assignedClientIds(), true) ? 1 : 0;

            // Weighted score: favor relationship + experience, penalize workload.
            $score = 0.0;
            $score += $relationship * 3.0;
            $score += min(5.0, $completed / 10.0);
            $score += min(4.0, $sameTypeCompleted / 5.0);
            $score -= min(6.0, $openAssigned / 2.0);

            $reason = [];
            if ($relationship) $reason[] = 'Already assigned to this client';
            $reason[] = "Open workload: {$openAssigned}";
            $reason[] = "Completed requests: {$completed}";
            if ($sameTypeCompleted > 0) $reason[] = "Completed {$sameTypeCompleted} similar ({$request->type}) requests";

            $suggestions[] = [
                'user_id' => (int) $u->id,
                'name' => (string) $u->name,
                'score' => round($score, 3),
                'reasoning' => implode(' · ', $reason),
            ];
        }

        usort($suggestions, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($suggestions, 0, 3);
    }

    /**
     * Apply triage results to the request and create an internal note for admins.
     *
     * This is intentionally conservative: only overwrites type/priority when request is draft-ish and values are default-ish.
     */
    public function applyTriage(ServiceRequest $request, array $triage): void
    {
        $updates = [];

        $suggestedType = (string) ($triage['suggested_request_type'] ?? '');
        $suggestedPriority = (string) ($triage['suggested_priority'] ?? '');
        $estimatedHours = $triage['estimated_hours'] ?? null;

        if (in_array($request->status, ['draft', 'pending'], true)) {
            if ($suggestedType !== '' && in_array($request->type, ['support', 'other'], true)) {
                $updates['type'] = $this->normalizeRequestType($suggestedType);
            }

            if ($suggestedPriority !== '' && $request->priority === 'medium') {
                $updates['priority'] = $this->normalizePriority($suggestedPriority);
            }
        }

        if ($estimatedHours !== null && $request->estimated_hours === null) {
            $hours = (float) $estimatedHours;
            if ($hours >= 0) {
                $updates['estimated_hours'] = $hours;
            }
        }

        if ($updates !== []) {
            $request->update($updates);
        }

        $assignment = $this->suggestAssignment($request);

        $note = $this->formatInternalNote($triage, $assignment);
        $this->logInternalNote($request, $note, [
            'triage' => $triage,
            'assignment_suggestions' => $assignment,
            'applied_updates' => $updates,
        ]);
    }

    /**
     * @return array<int, array{filename:string, mime_type:?string, size_bytes:?int}>
     */
    protected function attachmentMetadata(ServiceRequest $request): array
    {
        return RequestAttachment::query()
            ->where('request_id', $request->id)
            ->orderBy('id')
            ->get()
            ->map(fn (RequestAttachment $a) => [
                'filename' => (string) ($a->original_filename ?? $a->filename ?? ''),
                'mime_type' => $a->mime_type ? (string) $a->mime_type : null,
                'size_bytes' => $a->file_size !== null ? (int) $a->file_size : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Suggest similar past requests (basic keyword matching).
     *
     * @return array<int, array{id:int, title:string, status:string, type:string, priority:string}>
     */
    public function suggestSimilarPastRequests(ServiceRequest $request): array
    {
        $clientId = (int) $request->client_id;
        if ($clientId <= 0) return [];

        $terms = $this->keywordsFromText($request->title . ' ' . $request->description);
        if ($terms === []) return [];

        $q = ServiceRequest::query()
            ->where('client_id', $clientId)
            ->where('id', '!=', $request->id)
            ->orderByDesc('id');

        // Basic OR LIKE across top terms.
        $q->where(function ($qq) use ($terms) {
            foreach (array_slice($terms, 0, 5) as $t) {
                $qq->orWhere('title', 'like', '%' . $t . '%')
                    ->orWhere('description', 'like', '%' . $t . '%');
            }
        });

        return $q->limit(5)->get(['id', 'title', 'status', 'type', 'priority'])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'title' => (string) $r->title,
                'status' => (string) $r->status,
                'type' => (string) $r->type,
                'priority' => (string) $r->priority,
            ])
            ->all();
    }

    protected function normalizeRequestType(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = array_keys((array) config('client-portal.request_types', []));
        if (in_array($type, $allowed, true)) {
            return $type;
        }

        // Map category-ish values to existing types.
        return match ($type) {
            'development' => 'web_development',
            'design' => 'graphic_design',
            'marketing' => 'marketing',
            'consulting' => 'consulting',
            default => 'other',
        };
    }

    protected function normalizePriority(string $priority): string
    {
        $p = strtolower(trim($priority));
        return in_array($p, ['low', 'medium', 'high', 'urgent'], true) ? $p : 'medium';
    }

    /**
     * Extract the first valid JSON object from an LLM response.
     *
     * @return array<string, mixed>
     */
    protected function parseJsonFromText(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];

        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        // Try to extract the first {...} block.
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) return $decoded;
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function keywordsFromText(string $text): array
    {
        $t = strtolower($text);
        $t = preg_replace('/[^a-z0-9\s]/', ' ', $t) ?? $t;
        $parts = preg_split('/\s+/', $t) ?: [];
        $stop = ['the', 'and', 'for', 'with', 'this', 'that', 'from', 'your', 'our', 'you', 'are', 'need', 'want', 'have', 'make', 'build', 'help'];

        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '' || strlen($p) < 4) continue;
            if (in_array($p, $stop, true)) continue;
            $out[] = $p;
        }

        $out = array_values(array_unique($out));
        return array_slice($out, 0, 12);
    }

    /**
     * @param array<int, array{user_id:int, name:string, score:float, reasoning:string}> $assignment
     */
    protected function formatInternalNote(array $triage, array $assignment): string
    {
        $lines = [];
        $lines[] = 'AI Triage Summary:';
        $lines[] = (string) ($triage['summary_for_admin'] ?? '(no summary)');

        $lines[] = '';
        $lines[] = 'Suggested classification:';
        $lines[] = '- category: ' . (string) ($triage['category'] ?? '');
        $lines[] = '- subcategory: ' . (string) ($triage['subcategory'] ?? '');
        $lines[] = '- request_type: ' . (string) ($triage['suggested_request_type'] ?? '');
        $lines[] = '- priority: ' . (string) ($triage['suggested_priority'] ?? '');
        $lines[] = '- complexity: ' . (string) ($triage['complexity_score'] ?? '');
        $lines[] = '- estimated_hours: ' . (string) ($triage['estimated_hours'] ?? '');

        $kw = (array) ($triage['keywords'] ?? []);
        if ($kw !== []) $lines[] = '- keywords: ' . implode(', ', array_map('strval', $kw));

        $skills = (array) ($triage['required_skills'] ?? []);
        if ($skills !== []) $lines[] = '- skills: ' . implode(', ', array_map('strval', $skills));

        $issues = (array) ($triage['potential_issues'] ?? []);
        if ($issues !== []) {
            $lines[] = '';
            $lines[] = 'Potential issues:';
            foreach ($issues as $i) $lines[] = '- ' . (string) $i;
        }

        $amb = (array) ($triage['ambiguities'] ?? []);
        if ($amb !== []) {
            $lines[] = '';
            $lines[] = 'Ambiguities / missing info:';
            foreach ($amb as $a) $lines[] = '- ' . (string) $a;
        }

        $qs = (array) ($triage['next_questions_for_client'] ?? []);
        if ($qs !== []) {
            $lines[] = '';
            $lines[] = 'Suggested questions for client:';
            foreach ($qs as $q) $lines[] = '- ' . (string) $q;
        }

        if ($assignment !== []) {
            $lines[] = '';
            $lines[] = 'Top staff assignment suggestions:';
            foreach ($assignment as $s) {
                $lines[] = "- {$s['name']} (score {$s['score']}): {$s['reasoning']}";
            }
        }

        return implode("\n", $lines);
    }

    protected function logInternalNote(ServiceRequest $request, string $note, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => null,
                'client_id' => (int) $request->client_id,
                'log_name' => 'requests',
                'description' => $note,
                'subject_type' => ServiceRequest::class,
                'subject_id' => (int) $request->id,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => $properties,
                'event' => 'ai_triage',
                'ip_address' => null,
                'user_agent' => null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write AI triage internal note: ' . $e->getMessage());
        }
    }
}

