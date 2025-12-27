<?php

namespace App\Services\Marketing;

use App\Models\Client;
use App\Models\ContentCalendarItem;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Arr;

class ContentPlannerService
{
    public function __construct(private readonly AIProviderManager $ai) {}

    /**
     * @param  array<int,string>  $channels
     * @return array<string,mixed>
     */
    public function generateContentCalendar(Client $client, string $duration, array $channels, array $options = []): array
    {
        $channels = array_values(array_filter(array_map('strval', $channels)));

        $prompt = [
            'client' => [
                'company_name' => $client->company_name,
                'industry' => $client->industry,
                'website' => $client->website,
            ],
            'duration' => $duration,
            'channels' => $channels,
            'goals' => $options['goals'] ?? null,
            'task' => 'Create a content calendar. Output strict JSON with keys: pillars[], themes[], calendar_items[] (date,title,channel,platform,content_type,brief,hashtags).',
        ];

        $res = $this->ai->withFallback('claude', function ($provider) use ($prompt) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a content strategist. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'content_calendar_generation',
                'timeout' => 90,
                'max_tokens' => 1500,
            ]);
        }, taskType: 'content_calendar_generation');

        $json = $this->tryParseJson((string) ($res['text'] ?? '')) ?? [];
        $items = is_array($json['calendar_items'] ?? null) ? (array) $json['calendar_items'] : [];

        // Persist calendar items (optional)
        $persist = (bool) ($options['persist'] ?? false);
        $saved = 0;
        if ($persist) {
            foreach ($items as $i) {
                if (! is_array($i)) {
                    continue;
                }
                ContentCalendarItem::create([
                    'client_id' => $client->id,
                    'title' => (string) ($i['title'] ?? 'Content'),
                    'content_type' => (string) ($i['content_type'] ?? 'social'),
                    'platform' => isset($i['platform']) ? (string) $i['platform'] : null,
                    'scheduled_for' => isset($i['date']) ? (string) $i['date'] : null,
                    'status' => 'draft',
                    'content_text' => (string) ($i['brief'] ?? ''),
                    'hashtags' => is_array($i['hashtags'] ?? null) ? implode(', ', array_map('strval', $i['hashtags'])) : (string) ($i['hashtags'] ?? ''),
                    'campaign_tag' => isset($i['campaign_tag']) ? (string) $i['campaign_tag'] : null,
                    'created_by' => $options['created_by'] ?? null,
                    'meta' => Arr::except($i, ['title', 'content_type', 'platform', 'date', 'brief', 'hashtags']),
                ]);
                $saved++;
            }
        }

        return [
            'client_id' => $client->id,
            'duration' => $duration,
            'channels' => $channels,
            'pillars' => $json['pillars'] ?? [],
            'themes' => $json['themes'] ?? [],
            'calendar_items' => $items,
            'saved' => $saved,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function contentIdeation(string $industry, string $audience, string $goals): array
    {
        $prompt = [
            'industry' => $industry,
            'audience' => $audience,
            'goals' => $goals,
            'task' => 'Suggest topic ideas, seasonal ideas, and a content gap hypothesis. Output strict JSON with keys: topics[], seasonal[], gaps[], formats[].',
        ];

        $res = $this->ai->withFallback('perplexity', function ($provider) use ($prompt) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a content researcher. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'content_ideation',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'content_ideation');

        return $this->tryParseJson((string) ($res['text'] ?? '')) ?? ['raw' => (string) ($res['text'] ?? '')];
    }

    protected function tryParseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }
}
