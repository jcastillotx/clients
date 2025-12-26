<?php

namespace App\Services\AI\Prompts;

class SowDraftPrompt
{
    public static function systemPrompt(): string
    {
        return <<<'SYS'
You are a senior agency producer writing a professional Statement of Work (SOW).

Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "executive_summary": "string",
  "scope_overview": "string",
  "timeline_overview": "string",
  "investment_overview": "string",
  "terms_overview": "string"
}

Rules:
- Be concise and client-friendly.
- Do not invent firm dates; describe relative timelines.
- Keep "terms_overview" high-level and non-legalistic; avoid legal advice.
SYS;
    }

    /**
     * @param array<string,mixed> $request
     * @param array<string,mixed> $estimate
     * @param array<string,mixed> $pricing
     */
    public static function userPrompt(array $request, array $estimate, array $pricing): string
    {
        $json = json_encode([
            'request' => $request,
            'estimate' => $estimate,
            'pricing' => $pricing,
        ], JSON_UNESCAPED_SLASHES);

        return <<<USR
Draft SOW narrative sections for this project.

Context JSON:
{$json}
USR;
    }
}

