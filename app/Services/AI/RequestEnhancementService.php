<?php

namespace App\Services\AI;

use App\Models\Request as ServiceRequest;
use Illuminate\Support\Facades\Log;

class RequestEnhancementService
{
    public function __construct(
        protected AIProviderManager $providers
    ) {
    }

    /**
     * Identify vague/incomplete info and generate clarifying questions for the client.
     *
     * @return array{
     *   missing_information:array<int,string>,
     *   clarifying_questions:array<int,string>,
     *   suggested_details:array<int,string>,
     *   summary:string
     * }
     */
    public function clarifyRequest(ServiceRequest $request, array $options = []): array
    {
        $request->loadMissing(['client', 'creator', 'attachments']);

        $system = <<<'SYS'
You are an expert project manager for a digital agency.
Your job is to identify missing/vague information and produce client-friendly clarifying questions.

Return ONLY valid JSON (no markdown) in this schema:
{
  "missing_information": ["string", ...],
  "clarifying_questions": ["string", ...],
  "suggested_details": ["string", ...],
  "summary": "string"
}
SYS;

        $user = <<<USR
Request:
- id: {$request->id}
- title: {$request->title}
- description: {$request->description}
- type: {$request->type}
- priority: {$request->priority}

Create clarifying questions and missing info list.
USR;

        $target = $this->providers->routeToOptimalProvider('triage_request', (string) ($options['complexity'] ?? 'medium'));
        $preferred = (string) ($options['provider'] ?? $target['provider'] ?? 'openai');
        $model = (string) ($options['model'] ?? $target['model'] ?? '');

        $res = $this->providers->withFallback($preferred, function ($provider) use ($system, $user, $request, $options, $model) {
            return $provider->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], [
                'model' => $model !== '' ? $model : null,
                'task_type' => 'triage_request',
                'client_id' => $request->client_id,
                'user_id' => $request->created_by,
                'timeout' => (int) ($options['timeout'] ?? 60),
                'task_id' => $options['task_id'] ?? null,
            ]);
        }, 'clarify_request');

        return $this->parseJsonFromText((string) ($res['text'] ?? ''));
    }

    /**
     * Generate a detailed scope document with phases/milestones, deliverables, and dependencies.
     *
     * @return array{
     *   scope_markdown:string,
     *   phases:array<int, array{name:string, milestones:array<int,string>, deliverables:array<int,string>, dependencies:array<int,string>}>,
     *   assumptions:array<int,string>,
     *   risks:array<int,string>
     * }
     */
    public function generateScope(ServiceRequest $request, array $options = []): array
    {
        $request->loadMissing(['client', 'creator', 'attachments']);

        $system = <<<'SYS'
You are a senior delivery lead for a digital agency.
Generate a clear project scope that can be reviewed internally and shared with a client after edits.

Return ONLY valid JSON (no markdown fences) in this schema:
{
  "scope_markdown": "string",
  "phases": [
    {
      "name": "string",
      "milestones": ["string", ...],
      "deliverables": ["string", ...],
      "dependencies": ["string", ...]
    }
  ],
  "assumptions": ["string", ...],
  "risks": ["string", ...]
}
SYS;

        $user = <<<USR
Request:
- id: {$request->id}
- title: {$request->title}
- description: {$request->description}
- type: {$request->type}
- priority: {$request->priority}

Create a scope that breaks down work into phases, milestones, deliverables, and dependencies.
USR;

        $target = $this->providers->routeToOptimalProvider('generate_estimate', (string) ($options['complexity'] ?? 'high'));
        $preferred = (string) ($options['provider'] ?? $target['provider'] ?? 'openai');
        $model = (string) ($options['model'] ?? $target['model'] ?? '');

        $res = $this->providers->withFallback($preferred, function ($provider) use ($system, $user, $request, $options, $model) {
            return $provider->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], [
                'model' => $model !== '' ? $model : null,
                'task_type' => 'generate_estimate',
                'client_id' => $request->client_id,
                'user_id' => $request->created_by,
                'timeout' => (int) ($options['timeout'] ?? 90),
                'task_id' => $options['task_id'] ?? null,
            ]);
        }, 'generate_scope');

        return $this->parseJsonFromText((string) ($res['text'] ?? ''));
    }

    /**
     * @return array<string, mixed>
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

        Log::warning('AI enhancement returned non-JSON output.');
        return [];
    }
}

