<?php

namespace App\Services\AI;

use App\Services\AI\Prompts\CodeReviewPrompts;

class CodeReviewService
{
    public function __construct(protected AISafetyService $safety) {}

    /**
     * @param  array<int,array{path:string,content:string,language?:string}>  $codeFiles
     * @return array<string,mixed>
     */
    public function reviewCode(array $codeFiles, array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? 'claude');
        $taskType = (string) ($options['task_type'] ?? 'code_review');

        $messages = [
            ['role' => 'system', 'content' => CodeReviewPrompts::reviewSystem()],
            ['role' => 'user', 'content' => CodeReviewPrompts::reviewUser($codeFiles, $options['context'] ?? [])],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => $provider,
                'model' => $options['model'] ?? null,
                'task_type' => $taskType,
                'timeout' => $options['timeout'] ?? 240,
                'client_id' => $options['client_id'] ?? null,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => $options['user_query'] ?? 'Code review',
            ]);

            $data = $this->parseJson((string) ($res['text'] ?? ''));
            if (is_array($data)) {
                $data['_meta'] = [
                    'provider' => $res['provider'] ?? $provider,
                    'model' => $res['model'] ?? ($options['model'] ?? null),
                    'quality_score' => $res['quality_score'] ?? null,
                    'review_queue_id' => $res['review_queue_id'] ?? null,
                ];

                return $data;
            }
        } catch (\Throwable) {
            // best-effort fallback
        }

        return $this->fallbackReview($codeFiles);
    }

    /**
     * @return array<string,mixed>
     */
    public function generateDocumentation(string $code, array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? 'claude');
        $messages = [
            ['role' => 'system', 'content' => CodeReviewPrompts::docsSystem()],
            ['role' => 'user', 'content' => CodeReviewPrompts::docsUser($code, $options['context'] ?? [])],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => $provider,
                'model' => $options['model'] ?? null,
                'task_type' => $options['task_type'] ?? 'code_docs_generate',
                'timeout' => $options['timeout'] ?? 240,
                'client_id' => $options['client_id'] ?? null,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => $options['user_query'] ?? 'Generate documentation',
            ]);

            $data = $this->parseJson((string) ($res['text'] ?? ''));

            return is_array($data) ? $data : ['error' => 'Unable to parse documentation JSON.'];
        } catch (\Throwable $e) {
            return [
                'readme_md' => '',
                'api_docs_md' => '',
                'inline_comment_suggestions' => [],
                'public_interfaces' => [],
                'assumptions' => ['AI not configured: '.$e->getMessage()],
                '_meta' => ['fallback' => true],
            ];
        }
    }

    /**
     * Technical architecture review (design doc / system overview text).
     *
     * @return array<string,mixed>
     */
    public function reviewArchitecture(string $designDoc, array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? 'claude');
        $messages = [
            ['role' => 'system', 'content' => CodeReviewPrompts::architectureSystem()],
            ['role' => 'user', 'content' => "Review this architecture document:\n\n".$designDoc],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => $provider,
                'model' => $options['model'] ?? null,
                'task_type' => $options['task_type'] ?? 'architecture_review',
                'timeout' => $options['timeout'] ?? 240,
                'client_id' => $options['client_id'] ?? null,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => $options['user_query'] ?? 'Architecture review',
            ]);
            $data = $this->parseJson((string) ($res['text'] ?? ''));

            return is_array($data) ? $data : ['error' => 'Unable to parse architecture JSON.'];
        } catch (\Throwable $e) {
            return [
                'summary' => 'AI unavailable.',
                'scalability_risks' => [],
                'security_risks' => [],
                'performance_bottlenecks' => [],
                'recommended_architecture_changes' => [],
                'open_questions' => [],
                'confidence' => 'low',
                '_meta' => ['fallback' => true, 'error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Debugging assistant: analyze logs/errors.
     *
     * @return array<string,mixed>
     */
    public function debugLogs(string $logs, array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? 'claude');
        $messages = [
            ['role' => 'system', 'content' => CodeReviewPrompts::debugSystem()],
            ['role' => 'user', 'content' => "Analyze these logs:\n\n".$logs],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => $provider,
                'model' => $options['model'] ?? null,
                'task_type' => $options['task_type'] ?? 'debug_assistant',
                'timeout' => $options['timeout'] ?? 180,
                'client_id' => $options['client_id'] ?? null,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => $options['user_query'] ?? 'Debug logs',
            ]);
            $data = $this->parseJson((string) ($res['text'] ?? ''));

            return is_array($data) ? $data : ['error' => 'Unable to parse debug JSON.'];
        } catch (\Throwable $e) {
            return [
                'suspected_root_causes' => [],
                'recommended_fixes' => [],
                'debug_steps' => [],
                'notes' => 'AI unavailable: '.$e->getMessage(),
                '_meta' => ['fallback' => true],
            ];
        }
    }

    /**
     * Code generation assistant (boilerplate/endpoints/tests/migrations).
     *
     * @param  array<string,mixed>  $spec
     * @return array<string,mixed>
     */
    public function generateCode(array $spec, array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? 'openai');
        $messages = [
            ['role' => 'system', 'content' => CodeReviewPrompts::codegenSystem()],
            ['role' => 'user', 'content' => "Generate code from this spec (JSON):\n".json_encode($spec, JSON_UNESCAPED_SLASHES)],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => $provider,
                'model' => $options['model'] ?? null,
                'task_type' => $options['task_type'] ?? 'code_generation',
                'timeout' => $options['timeout'] ?? 240,
                'client_id' => $options['client_id'] ?? null,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => $options['user_query'] ?? 'Generate code',
            ]);
            $data = $this->parseJson((string) ($res['text'] ?? ''));

            return is_array($data) ? $data : ['error' => 'Unable to parse codegen JSON.'];
        } catch (\Throwable $e) {
            return [
                'files' => [],
                'notes' => ['AI unavailable: '.$e->getMessage()],
                'tests' => [],
                'migrations' => [],
                'config_changes' => [],
                '_meta' => ['fallback' => true],
            ];
        }
    }

    /**
     * Tech stack recommendation based on requirements.
     *
     * @param  array<string,mixed>  $requirements
     * @return array<string,mixed>
     */
    public function recommendTechStack(array $requirements, array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? 'claude');
        $messages = [
            ['role' => 'system', 'content' => CodeReviewPrompts::stackSystem()],
            ['role' => 'user', 'content' => "Recommend a tech stack for these requirements (JSON):\n".json_encode($requirements, JSON_UNESCAPED_SLASHES)],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => $provider,
                'model' => $options['model'] ?? null,
                'task_type' => $options['task_type'] ?? 'tech_stack_recommendation',
                'timeout' => $options['timeout'] ?? 240,
                'client_id' => $options['client_id'] ?? null,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => $options['user_query'] ?? 'Tech stack recommendation',
            ]);
            $data = $this->parseJson((string) ($res['text'] ?? ''));

            return is_array($data) ? $data : ['error' => 'Unable to parse stack JSON.'];
        } catch (\Throwable $e) {
            return [
                'recommended_stack' => [],
                'alternatives' => [],
                'team_fit_notes' => [],
                'cost_notes' => [],
                'scalability_notes' => [],
                'confidence' => 'low',
                '_meta' => ['fallback' => true, 'error' => $e->getMessage()],
            ];
        }
    }

    // -------------------------
    // Helpers
    // -------------------------

    protected function parseJson(string $text): ?array
    {
        $t = trim($text);
        if ($t === '') {
            return null;
        }
        $decoded = json_decode($t, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($t, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param  array<int,array{path:string,content:string,language?:string}>  $codeFiles
     * @return array<string,mixed>
     */
    protected function fallbackReview(array $codeFiles): array
    {
        $fileCount = count($codeFiles);
        $findings = [];

        foreach ($codeFiles as $f) {
            $path = (string) ($f['path'] ?? null);
            $content = (string) ($f['content'] ?? '');
            if (preg_match('/sk-[A-Za-z0-9]{20,}/', $content) || preg_match('/AKIA[0-9A-Z]{16}/', $content)) {
                $findings[] = [
                    'type' => 'security',
                    'severity' => 'critical',
                    'file' => $path ?: null,
                    'line' => null,
                    'title' => 'Potential secret found in code',
                    'details' => 'A string resembling an API key was detected. Remove it and rotate credentials.',
                    'suggested_fix' => 'Move secrets to environment variables and rotate the exposed key.',
                    'confidence' => 'high',
                ];
            }
        }

        return [
            'summary' => "AI not configured. Performed basic heuristic checks on {$fileCount} file(s).",
            'risk_level' => empty($findings) ? 'low' : 'critical',
            'findings' => $findings,
            'quick_wins' => empty($findings) ? ['Enable AI to get a deeper review.'] : ['Remove detected secrets and rotate keys.'],
            'recommended_tests' => ['Add unit tests for critical business logic and authorization paths.'],
            'security_notes' => empty($findings) ? ['Run dependency scanning and secrets detection in CI.'] : ['Rotate any exposed secrets immediately.'],
            'compliance_notes' => ['Ensure secrets are not committed; review logging for PII.'],
            'overall_recommendation' => empty($findings) ? 'request_changes' : 'block',
            '_meta' => ['fallback' => true],
        ];
    }
}
