<?php

namespace App\Services\AI\Prompts;

use App\Models\Request as ServiceRequest;

class RequestTriagePrompt
{
    public static function systemPrompt(): string
    {
        return <<<'SYS'
You are an operations assistant for a digital agency. You triage incoming client requests for an internal team.

Return ONLY valid JSON that matches the schema described below. No markdown. No extra keys.

Schema:
{
  "category": "design|development|marketing|consulting|support",
  "subcategory": "string",
  "suggested_request_type": "one of: web_development|graphic_design|marketing|seo|social_media|branding|consulting|maintenance|support|other",
  "suggested_priority": "low|medium|high|urgent",
  "complexity_score": 1-10,
  "required_skills": ["string", ...],
  "required_departments": ["string", ...],
  "estimated_hours": number,
  "keywords": ["string", ...],
  "ambiguities": ["string", ...],
  "potential_issues": ["string", ...],
  "next_questions_for_client": ["string", ...],
  "summary_for_admin": "string",
  "recommended_actions": ["string", ...]
}

Guidelines:
- Base decisions on the request title/description and attachment list only (you cannot open files).
- If deadlines/urgency are unclear, do NOT mark urgent; ask clarifying questions instead.
- Use conservative estimates; if you are unsure, estimate a range by choosing a midpoint and note uncertainty in ambiguities.
SYS;
    }

    /**
     * @param array<int, array{filename:string, mime_type:?string, size_bytes:?int}> $attachments
     * @param array<int, array{id:int, title:string, status:string, type:string, priority:string}> $similar
     */
    public static function userPrompt(ServiceRequest $request, array $attachments = [], array $similar = []): string
    {
        $att = $attachments === [] ? '[]' : json_encode($attachments, JSON_UNESCAPED_SLASHES);
        $sim = $similar === [] ? '[]' : json_encode($similar, JSON_UNESCAPED_SLASHES);

        return <<<USR
New client request:
- id: {$request->id}
- current_type: {$request->type}
- current_priority: {$request->priority}
- title: {$request->title}
- description: {$request->description}

Attachments (metadata only):
{$att}

Similar past requests (metadata only):
{$sim}

Produce the JSON triage output now.
USR;
    }
}

