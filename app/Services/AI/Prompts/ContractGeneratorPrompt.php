<?php

namespace App\Services\AI\Prompts;

use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\Project;
use App\Models\Request as ServiceRequest;

class ContractGeneratorPrompt
{
    public static function systemPrompt(): string
    {
        return <<<'SYS'
You are an assistant that drafts client service contracts for a digital agency.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "html":"string",
  "notes":"string"
}

Rules:
- Produce clean HTML suitable for PDF rendering (simple tags: h1,h2,p,ul,li,strong,em,table,tr,td).
- Use the template body as the base and fill placeholders conservatively.
- Adjust payment terms based on client tier when provided (premium clients may get better net terms).
- Include deliverables and timeline based on project/request context.
- Do not provide legal advice; keep language standard and neutral.
SYS;
    }

    public static function userPrompt(Client $client, ?Project $project, ?ServiceRequest $request, DocumentTemplate $template): string
    {
        $ctx = json_encode([
            'client' => [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'tier' => $client->tier,
            ],
            'project' => $project ? [
                'id' => $project->id,
                'title' => $project->title ?? null,
                'status' => $project->status ?? null,
            ] : null,
            'request' => $request ? [
                'id' => $request->id,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'priority' => $request->priority,
                'estimated_hours' => $request->estimated_hours,
            ] : null,
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->category,
                'variables' => $template->variables,
            ],
        ], JSON_UNESCAPED_SLASHES);

        return <<<USR
Context:
{$ctx}

Template body (fill it in):
{$template->body}
USR;
    }
}

