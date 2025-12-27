<?php

namespace App\Services\AI\Prompts;

class CommunicationPrompts
{
    public static function draftEmailSystem(): string
    {
        return <<<'SYS'
You are a communication assistant for a digital agency. Write concise, professional emails.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "subject":"string",
  "body":"string",
  "short_bullets":["string",...]
}

Rules:
- Use the requested tone.
- Keep body under ~250 words unless the context demands more.
- Never invent facts. If missing details, include a polite placeholder question.
SYS;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public static function draftEmailUser(array $context, string $purpose, string $tone): string
    {
        $payload = json_encode([
            'purpose' => $purpose,
            'tone' => $tone,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES);

        return <<<USR
Draft an email.

Context JSON:
{$payload}
USR;
    }

    public static function smartRepliesSystem(): string
    {
        return <<<'SYS'
You are a client support assistant. Suggest 3 short reply options that are professional and on-brand.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "recommended_tone":"formal|friendly|urgent",
  "replies":[
    {"title":"string","text":"string"},
    {"title":"string","text":"string"},
    {"title":"string","text":"string"}
  ]
}

Rules:
- Replies must be ready-to-send, but not overly definitive if details are missing.
- Keep each reply under ~80 words.
SYS;
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $history
     * @param  array<string,mixed>  $context
     */
    public static function smartRepliesUser(string $clientMessage, array $context, array $history = []): string
    {
        $payload = json_encode([
            'client_message' => $clientMessage,
            'context' => $context,
            'history' => $history,
        ], JSON_UNESCAPED_SLASHES);

        return <<<USR
Suggest quick replies for this client message.

Context JSON:
{$payload}
USR;
    }

    public static function improveWritingSystem(): string
    {
        return <<<'SYS'
You are a writing assistant. Improve grammar, clarity, and professionalism.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "improved_text":"string",
  "changes_summary":["string",...]
}
SYS;
    }

    public static function improveWritingUser(string $text, ?string $tone = null): string
    {
        $payload = json_encode([
            'tone' => $tone,
            'text' => $text,
        ], JSON_UNESCAPED_SLASHES);

        return <<<USR
Improve this text.
{$payload}
USR;
    }

    public static function sentimentSystem(): string
    {
        return <<<'SYS'
You analyze client sentiment and urgency.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "sentiment":"positive|neutral|negative",
  "urgency":"low|medium|high",
  "confidence":0-1,
  "signals":["string",...],
  "suggested_tone":"formal|friendly|urgent"
}
SYS;
    }

    public static function intentSystem(): string
    {
        return <<<'SYS'
You classify the intent of a client message.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "intent":"question|request|complaint|status_update|other",
  "categories":["string",...],
  "suggested_next_step":"string"
}
SYS;
    }

    public static function translateSystem(): string
    {
        return <<<'SYS'
You translate text while preserving professional tone.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "detected_language":"string|null",
  "translated_text":"string"
}
SYS;
    }

    public static function translateUser(string $text, string $targetLanguage): string
    {
        return "Target language: {$targetLanguage}\n\nText:\n{$text}";
    }
}
