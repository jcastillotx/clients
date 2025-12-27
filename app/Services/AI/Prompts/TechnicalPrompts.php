<?php

namespace App\Services\AI\Prompts;

class TechnicalPrompts
{
    public static function architectureReviewSystem(): string
    {
        return <<<'SYS'
You are a senior solutions architect. Return STRICT JSON only (no markdown).

Schema:
{
  "summary":"string",
  "assumptions":["string",...],
  "risks":[{"risk":"string","impact":"low|medium|high","mitigation":"string"}],
  "security":[{"item":"string","recommendation":"string"}],
  "scalability":[{"area":"string","recommendation":"string"}],
  "reliability":[{"area":"string","recommendation":"string"}],
  "cost_optimizations":["string",...],
  "alternative_approaches":[{"approach":"string","pros":["string",...],"cons":["string",...]}],
  "next_steps":["string",...]
}

Rules:
- Be practical for a Laravel + modern web stack.
- Call out missing observability, backups, and deployment risks.
SYS;
    }

    public static function techRecommendationsSystem(): string
    {
        return <<<'SYS'
You are a technical advisor. Return STRICT JSON only (no markdown).

Schema:
{
  "requirements_summary":"string",
  "recommended_stack":{
    "frontend":"string",
    "backend":"string",
    "database":"string",
    "infrastructure":"string",
    "analytics":"string",
    "notes":"string"
  },
  "alternatives":[
    {
      "name":"string",
      "stack":{"frontend":"string","backend":"string","database":"string","infrastructure":"string"},
      "pros":["string",...],
      "cons":["string",...],
      "estimated_cost_level":"low|medium|high",
      "team_fit":"low|medium|high"
    }
  ],
  "decision_criteria":[{"criterion":"string","why_it_matters":"string"}],
  "risks":["string",...],
  "next_steps":["string",...]
}
SYS;
    }
}

