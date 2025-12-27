<?php

namespace App\Services\AI\Prompts;

class CodeReviewPrompts
{
    public static function reviewSystem(): string
    {
        return <<<'SYS'
You are a senior software engineer performing a code review and security audit.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "summary":"string",
  "risk_level":"low|medium|high|critical",
  "findings":[
    {
      "type":"bug|security|performance|maintainability|style|testing|architecture",
      "severity":"low|medium|high|critical",
      "file":"string|null",
      "line":"string|null",
      "title":"string",
      "details":"string",
      "suggested_fix":"string",
      "confidence":"low|medium|high"
    }
  ],
  "quick_wins":["string",...],
  "recommended_tests":["string",...],
  "security_notes":["string",...],
  "compliance_notes":["string",...],
  "overall_recommendation":"approve|request_changes|block"
}

Rules:
- Be precise. If you can't justify it from the provided code/context, mark confidence low.
- Flag OWASP Top 10 risks where applicable (authz/authn, injection, XSS, CSRF, SSRF, deserialization, secrets, etc.).
- Do not fabricate dependencies or files not included in the input.
SYS;
    }

    /**
     * @param  array<int,array{path:string,content:string,language?:string}>  $codeFiles
     */
    public static function reviewUser(array $codeFiles, array $context = []): string
    {
        $payload = [
            'context' => $context,
            'files' => array_map(function ($f) {
                return [
                    'path' => (string) ($f['path'] ?? ''),
                    'language' => $f['language'] ?? null,
                    'content' => (string) ($f['content'] ?? ''),
                ];
            }, $codeFiles),
        ];

        return "Review these code files.\n\nJSON:\n".json_encode($payload, JSON_UNESCAPED_SLASHES)."\n\nReturn JSON in the schema.";
    }

    public static function docsSystem(): string
    {
        return <<<'SYS'
You are a technical writer and senior engineer generating documentation from code.
Return ONLY valid JSON. No markdown.

Schema:
{
  "readme_md":"string",
  "api_docs_md":"string",
  "inline_comment_suggestions":[{"file":"string|null","line":"string|null","comment":"string"}],
  "public_interfaces":[{"name":"string","kind":"class|function|endpoint|module","description":"string"}],
  "assumptions":["string",...]
}
SYS;
    }

    public static function docsUser(string $code, array $context = []): string
    {
        $payload = ['context' => $context, 'code' => $code];

        return "Generate documentation for this codebase snippet.\n\nJSON:\n".json_encode($payload, JSON_UNESCAPED_SLASHES)."\n\nReturn JSON in the schema.";
    }

    public static function architectureSystem(): string
    {
        return <<<'SYS'
You are a principal engineer reviewing a system design document.
Return ONLY valid JSON. No markdown.

Schema:
{
  "summary":"string",
  "scalability_risks":[{"risk":"string","impact":"string","mitigation":"string","severity":"low|medium|high|critical"}],
  "security_risks":[{"risk":"string","impact":"string","mitigation":"string","severity":"low|medium|high|critical"}],
  "performance_bottlenecks":[{"area":"string","issue":"string","recommendation":"string","severity":"low|medium|high"}],
  "recommended_architecture_changes":["string",...],
  "open_questions":["string",...],
  "confidence":"low|medium|high"
}
SYS;
    }

    public static function debugSystem(): string
    {
        return <<<'SYS'
You are a debugging assistant. Return ONLY valid JSON. No markdown.

Schema:
{
  "suspected_root_causes":[{"cause":"string","confidence":"low|medium|high","evidence":"string"}],
  "recommended_fixes":[{"fix":"string","priority":"low|medium|high","rationale":"string"}],
  "debug_steps":["string",...],
  "notes":"string"
}
SYS;
    }

    public static function codegenSystem(): string
    {
        return <<<'SYS'
You are a code generation assistant.
Return ONLY valid JSON. No markdown.

Schema:
{
  "files":[{"path":"string","content":"string"}],
  "notes":["string",...],
  "tests":["string",...],
  "migrations":["string",...],
  "config_changes":["string",...]
}

Rules:
- Keep output minimal and coherent.
- Do not include secrets.
SYS;
    }

    public static function stackSystem(): string
    {
        return <<<'SYS'
You are a technical advisor recommending a tech stack.
Return ONLY valid JSON. No markdown.

Schema:
{
  "recommended_stack":[{"category":"frontend|backend|database|infra|observability|ci","choice":"string","reason":"string"}],
  "alternatives":[{"stack":"string","pros":["string",...],"cons":["string",...]}],
  "team_fit_notes":["string",...],
  "cost_notes":["string",...],
  "scalability_notes":["string",...],
  "confidence":"low|medium|high"
}
SYS;
    }
}
