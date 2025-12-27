<?php

namespace App\Services\AI\Prompts;

class ProjectEstimationPrompt
{
    public static function systemPrompt(): string
    {
        return <<<'SYS'
You are a senior delivery lead at a digital agency. You create accurate, defensible project estimates.

Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "tasks": [
    {
      "name": "string",
      "description": "string",
      "hours_low": number,
      "hours_mid": number,
      "hours_high": number,
      "dependencies": ["string", ...],
      "assumptions": ["string", ...],
      "out_of_scope": ["string", ...]
    }
  ],
  "timeline": {
    "duration_weeks_low": number,
    "duration_weeks_mid": number,
    "duration_weeks_high": number,
    "milestones": ["string", ...]
  },
  "risk_factors": ["string", ...],
  "notes_for_admin": "string"
}

Rules:
- Be conservative. Prefer asking for clarification via assumptions vs guessing.
- Hours_low/mid/high must be consistent (low <= mid <= high).
- Keep tasks granular (5–15 tasks typical).
- Use the provided "similar projects" and "historical variance" to calibrate effort.
SYS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $similarProjects
     * @param  array{count:int, median_ratio:float|null}  $variance
     */
    public static function userPrompt(array $request, array $similarProjects, array $variance, float $hourlyRate): string
    {
        $varianceLine = $variance['median_ratio'] === null
            ? 'No variance stats available.'
            : ('Median actual/estimated ratio from similar projects: '.number_format((float) $variance['median_ratio'], 2).' (n='.(int) $variance['count'].').');

        $json = json_encode([
            'request' => $request,
            'similar_projects' => $similarProjects,
            'historical_variance' => $variance,
            'hourly_rate' => $hourlyRate,
        ], JSON_UNESCAPED_SLASHES);

        return <<<USR
Estimate this request.

{$varianceLine}

Context JSON:
{$json}
USR;
    }
}
