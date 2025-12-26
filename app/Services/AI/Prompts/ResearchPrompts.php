<?php

namespace App\Services\AI\Prompts;

use App\Models\Client;

class ResearchPrompts
{
    public static function researchSystem(): string
    {
        return <<<'SYS'
You are a research assistant for a digital agency.
Your output must be strictly JSON. No markdown.

Schema:
{
  "topic": "string",
  "depth": "quick|standard|deep",
  "executive_summary": "string",
  "key_findings": [{"title":"string","detail":"string","confidence":"low|medium|high"}],
  "data_points": [{"label":"string","value":"string","notes":"string"}],
  "opportunities": ["string", ...],
  "risks": ["string", ...],
  "recommended_next_steps": ["string", ...],
  "sources": [{"title":"string","url":"string","publisher":"string","published_at":"string|null","why_it_matters":"string"}],
  "search_queries_used": ["string", ...]
}

Rules:
- Use web-grounded claims only; if unsure, mark confidence low.
- Include 5-15 sources for standard/deep (3-8 for quick).
- Do not invent URLs.
SYS;
    }

    public static function researchUser(string $topic, string $depth, ?string $region = null): string
    {
        $regionLine = $region ? "Region focus: {$region}\n" : '';
        return "Topic: {$topic}\nDepth: {$depth}\n{$regionLine}Return JSON in the schema.";
    }

    public static function competitiveSystem(): string
    {
        return <<<'SYS'
You are a strategy analyst. Return STRICT JSON only (no markdown).

Schema:
{
  "client": {"name":"string","industry":"string|null","region":"string|null"},
  "competitors": [
    {
      "name":"string",
      "positioning":"string",
      "pricing_notes":"string",
      "key_offerings":["string",...],
      "strengths":["string",...],
      "weaknesses":["string",...],
      "evidence_sources":[{"title":"string","url":"string"}]
    }
  ],
  "comparison": {
    "feature_matrix": [{"feature":"string","client":"string","competitor":"string","notes":"string"}],
    "pricing_comparison": [{"vendor":"string","price_range":"string","notes":"string"}],
    "positioning_summary":"string"
  },
  "swot": {
    "strengths":["string",...],
    "weaknesses":["string",...],
    "opportunities":["string",...],
    "threats":["string",...]
  },
  "recommendations": ["string", ...],
  "sources":[{"title":"string","url":"string","why_it_matters":"string"}]
}

Rules:
- Use citations for claims about competitors.
- If pricing is not public, say so and cite what you can.
SYS;
    }

    public static function competitiveUser(Client $client, array $competitors): string
    {
        $comp = implode(', ', array_values(array_filter(array_map('trim', $competitors))));
        $industry = $client->industry ?: null;
        $region = $client->country ?: null;

        $ctx = [
            'name' => (string) ($client->company_name ?? $client->contact_name ?? "Client #{$client->id}"),
            'industry' => $industry,
            'region' => $region,
        ];

        return "Client JSON:\n" . json_encode($ctx) . "\n\nCompetitors (names): {$comp}\nReturn JSON in the schema.";
    }

    public static function marketSystem(): string
    {
        return <<<'SYS'
You are a market analyst. Return STRICT JSON only (no markdown).

Schema:
{
  "industry":"string",
  "region":"string|null",
  "executive_summary":"string",
  "trends":[{"trend":"string","impact":"string","time_horizon":"0-6m|6-18m|18m+","confidence":"low|medium|high","sources":[{"title":"string","url":"string"}]}],
  "market_size":"string",
  "growth_outlook":"string",
  "key_players":[{"name":"string","notes":"string","sources":[{"title":"string","url":"string"}]}],
  "opportunities":["string",...],
  "threats":["string",...],
  "recommended_actions":["string",...],
  "sources":[{"title":"string","url":"string","why_it_matters":"string"}]
}

Rules:
- Prefer primary/credible sources; include citations.
- If market size cannot be verified, state uncertainty.
SYS;
    }

    public static function marketUser(string $industry, ?string $region): string
    {
        return "Industry: {$industry}\nRegion: " . ($region ?: 'null') . "\nReturn JSON in the schema.";
    }

    public static function seoSystem(): string
    {
        return <<<'SYS'
You are an SEO content strategist. Return STRICT JSON only (no markdown).

Schema:
{
  "topic":"string",
  "audience":"string",
  "search_intent":"informational|commercial|transactional|navigational",
  "keyword_clusters":[
    {"cluster":"string","primary_keywords":["string",...],"secondary_keywords":["string",...],"notes":"string"}
  ],
  "content_ideas":[{"title":"string","angle":"string","why_now":"string","estimated_difficulty":"low|medium|high"}],
  "outline":{"h1":"string","sections":[{"h2":"string","bullets":["string",...]}]},
  "competitor_content_notes":["string",...],
  "sources":[{"title":"string","url":"string","why_it_matters":"string"}]
}
SYS;
    }

    public static function seoUser(string $topic, string $audience, ?string $region = null): string
    {
        return "Topic: {$topic}\nAudience: {$audience}\nRegion: " . ($region ?: 'none') . "\nReturn JSON in the schema.";
    }

    public static function creativeSystem(): string
    {
        return <<<'SYS'
You are a creative director. Return STRICT JSON only (no markdown).

Schema:
{
  "campaign_ideas":[{"name":"string","concept":"string","channels":["string",...],"why_it_works":"string"}],
  "brand_names":[{"name":"string","rationale":"string","domain_ideas":["string",...]}],
  "taglines":[{"tagline":"string","tone":"string","notes":"string"}]
}
SYS;
    }
}

