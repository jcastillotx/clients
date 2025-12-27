<?php

namespace App\Services\AI\Prompts;

class MeetingPrompts
{
    public static function summarizeSystem(): string
    {
        return <<<'SYS'
You are a meeting assistant. Produce a clear summary and actionable follow-ups.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "summary":"string",
  "decisions":["string",...],
  "action_items":[{"item":"string","owner":"string|null","due":"string|null"}],
  "follow_up_tasks":["string",...]
}
SYS;
    }

    public static function summarizeUser(string $transcript, array $participants = []): string
    {
        $payload = json_encode([
            'participants' => $participants,
            'transcript' => $transcript,
        ], JSON_UNESCAPED_SLASHES);

        return "Summarize this meeting.\n\nContext JSON:\n{$payload}";
    }

    public static function agendaSystem(): string
    {
        return <<<'SYS'
You are a meeting facilitator. Create a practical agenda with timeboxes.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "agenda_title":"string",
  "timeboxes":[{"topic":"string","minutes":number,"notes":"string"}],
  "prep_notes":["string",...]
}
SYS;
    }

    public static function agendaUser(string $purpose, array $participants): string
    {
        $payload = json_encode([
            'purpose' => $purpose,
            'participants' => $participants,
        ], JSON_UNESCAPED_SLASHES);

        return "Generate a meeting agenda.\n\nContext JSON:\n{$payload}";
    }
}
