<?php

namespace App\Services\Marketing;

use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SEOAnalyzerService
{
    public function __construct(private readonly AIProviderManager $ai)
    {
    }

    /**
     * @param array<int,string> $keywords
     * @return array<string,mixed>
     */
    public function keywordAnalysis(string $url, array $keywords): array
    {
        $url = $this->normalizeUrl($url);
        $keywords = array_values(array_filter(array_map(fn ($k) => trim(mb_strtolower((string) $k)), $keywords)));

        $html = $this->fetchHtml($url);
        $text = $this->extractVisibleText($html);
        $title = $this->extractTagText($html, 'title');
        $h1 = $this->extractFirstHeading($html, 1);

        $totalWords = $this->wordCount($text);
        $byKeyword = [];

        foreach ($keywords as $kw) {
            $count = $this->countOccurrences($text, $kw);
            $density = $totalWords > 0 ? round(($count / $totalWords) * 100, 3) : null;
            $byKeyword[] = [
                'keyword' => $kw,
                'count' => $count,
                'density_percent' => $density,
                'placement' => [
                    'in_title' => $title !== null && Str::contains(mb_strtolower($title), $kw),
                    'in_h1' => $h1 !== null && Str::contains(mb_strtolower($h1), $kw),
                    'in_url' => Str::contains(mb_strtolower($url), $kw),
                ],
            ];
        }

        $lsi = $this->lsiSuggestions($url, $keywords, $text);

        return [
            'url' => $url,
            'total_words' => $totalWords,
            'keywords' => $byKeyword,
            'lsi_suggestions' => $lsi,
            'notes' => [
                'difficulty' => 'Requires external SEO data provider (Ahrefs/SEMrush/Moz) to be accurate.',
                'search_volume' => 'Requires external provider or Search Console query data.',
                'rank_tracking' => 'Tracked via keyword_rankings table + scheduled jobs (scaffolded).',
            ],
        ];
    }

    /**
     * @param array<int,string> $competitorUrls
     * @return array<string,mixed>
     */
    public function competitorKeywordGap(string $clientUrl, array $competitorUrls): array
    {
        $clientUrl = $this->normalizeUrl($clientUrl);
        $competitorUrls = array_values(array_filter(array_map([$this, 'normalizeUrl'], $competitorUrls)));

        // Production note: real keyword gap requires SERP/backlink providers. Here we provide an AI-assisted scaffold.
        $prompt = [
            'client' => $clientUrl,
            'competitors' => $competitorUrls,
            'task' => 'Identify likely keyword gaps and content opportunities. Return JSON with keys: competitor_keywords, gaps, topic_clusters, content_briefs.',
        ];

        $res = $this->ai->withFallback('perplexity', function ($provider) use ($prompt) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are an SEO strategist. Provide web-grounded suggestions when possible. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'seo_competitor_gap',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'seo_competitor_gap');

        $json = $this->tryParseJson((string) ($res['text'] ?? ''));

        return [
            'client_url' => $clientUrl,
            'competitors' => $competitorUrls,
            'analysis' => is_array($json) ? $json : ['raw' => (string) ($res['text'] ?? '')],
            'provider_raw' => $res,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function backlinkAnalysis(string $url): array
    {
        $url = $this->normalizeUrl($url);

        // Production note: requires provider API. Provide placeholders + configuration signals.
        return [
            'url' => $url,
            'providers_configured' => [
                'ahrefs' => (string) config('website-auditor.integrations.ahrefs.api_key', '') !== '',
                'semrush' => (string) config('website-auditor.integrations.semrush.api_key', '') !== '',
                'moz' => (string) config('website-auditor.integrations.moz.access_id', '') !== '' && (string) config('website-auditor.integrations.moz.secret_key', '') !== '',
            ],
            'metrics' => [
                'total_backlinks' => null,
                'referring_domains' => null,
                'anchor_text_distribution' => [],
                'toxic_links' => [],
                'link_velocity' => [],
                'lost_backlinks' => [],
            ],
            'notes' => 'Persist fetched backlinks into backlinks table for history/velocity/lost tracking.',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function contentOptimization(string $url, string $targetKeyword): array
    {
        $url = $this->normalizeUrl($url);
        $targetKeyword = trim(mb_strtolower($targetKeyword));

        $html = $this->fetchHtml($url);
        $text = $this->extractVisibleText($html);
        $wordCount = $this->wordCount($text);
        $readability = $this->fleschReadingEase($text);

        $prompt = [
            'url' => $url,
            'target_keyword' => $targetKeyword,
            'word_count' => $wordCount,
            'readability' => $readability,
            'sample' => mb_substr($text, 0, 4000),
        ];

        $res = $this->ai->withFallback('claude', function ($provider) use ($prompt) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are an SEO content editor. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'seo_content_optimization',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'seo_content_optimization');

        $json = $this->tryParseJson((string) ($res['text'] ?? ''));

        return [
            'url' => $url,
            'target_keyword' => $targetKeyword,
            'metrics' => [
                'word_count' => $wordCount,
                'flesch_reading_ease' => $readability,
            ],
            'recommendations' => is_array($json) ? $json : ['raw' => (string) ($res['text'] ?? '')],
        ];
    }

    /**
     * @param array<string,mixed> $businessInfo
     * @return array<string,mixed>
     */
    public function localSEOAudit(array $businessInfo): array
    {
        // Production note: true NAP/citations requires provider APIs. Provide AI-assisted checklist.
        $res = $this->ai->withFallback('claude', function ($provider) use ($businessInfo) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a local SEO specialist. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($businessInfo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'local_seo_audit',
                'timeout' => 90,
                'max_tokens' => 900,
            ]);
        }, taskType: 'local_seo_audit');

        return [
            'business' => $businessInfo,
            'audit' => $this->tryParseJson((string) ($res['text'] ?? '')) ?? ['raw' => (string) ($res['text'] ?? '')],
        ];
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return $url;
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    protected function fetchHtml(string $url): string
    {
        try {
            $resp = Http::timeout(20)->withHeaders([
                'User-Agent' => 'Kre8ivDesigns-SEOAnalyzer/1.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->get($url);

            return $resp->successful() ? (string) $resp->body() : '';
        } catch (\Throwable) {
            return '';
        }
    }

    protected function extractVisibleText(string $html): string
    {
        if ($html === '') return '';
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);
        foreach ($xp->query('//script|//style|//noscript') ?? [] as $n) {
            $n->parentNode?->removeChild($n);
        }
        $text = $doc->textContent ?? '';
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }

    protected function extractTagText(string $html, string $tag): ?string
    {
        if ($html === '') return null;
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();
        $nodes = $doc->getElementsByTagName($tag);
        if ($nodes->length < 1) return null;
        $t = trim((string) $nodes->item(0)?->textContent);
        return $t !== '' ? $t : null;
    }

    protected function extractFirstHeading(string $html, int $level): ?string
    {
        $tag = 'h' . max(1, min(6, $level));
        return $this->extractTagText($html, $tag);
    }

    protected function wordCount(string $text): int
    {
        $text = trim($text);
        if ($text === '') return 0;
        return count(preg_split('/\s+/u', $text) ?: []);
    }

    protected function countOccurrences(string $text, string $needle): int
    {
        if ($needle === '') return 0;
        $text = mb_strtolower($text);
        $needle = mb_strtolower($needle);
        return substr_count($text, $needle);
    }

    /**
     * @param array<int,string> $keywords
     * @return array<int,string>
     */
    protected function lsiSuggestions(string $url, array $keywords, string $pageText): array
    {
        if (empty($keywords)) return [];
        $prompt = [
            'url' => $url,
            'target_keywords' => $keywords,
            'page_sample' => mb_substr($pageText, 0, 2000),
            'task' => 'Suggest 10 LSI/semantic keywords closely related to the targets. Output JSON array of strings.',
        ];

        try {
            $res = $this->ai->withFallback('claude', function ($provider) use ($prompt) {
                return $provider->chat([
                    ['role' => 'system', 'content' => 'Output strict JSON only.'],
                    ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
                ], [
                    'task_type' => 'seo_lsi_suggestions',
                    'timeout' => 60,
                    'max_tokens' => 500,
                ]);
            }, taskType: 'seo_lsi_suggestions');

            $json = $this->tryParseJson((string) ($res['text'] ?? ''));
            return is_array($json) ? array_values(array_filter(array_map('strval', $json))) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    protected function fleschReadingEase(string $text): ?float
    {
        $text = trim($text);
        if ($text === '') return null;

        $sentences = max(1, preg_match_all('/[.!?]+/u', $text));
        $words = max(1, $this->wordCount($text));
        $syllables = max(1, $this->estimateSyllables($text));

        // English approximation: 206.835 − 1.015*(words/sentences) − 84.6*(syllables/words)
        return round(206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words)), 2);
    }

    protected function estimateSyllables(string $text): int
    {
        $text = mb_strtolower($text);
        $words = preg_split('/\s+/u', preg_replace('/[^a-z\s]/u', ' ', $text) ?? $text) ?: [];
        $count = 0;
        foreach ($words as $w) {
            $w = trim($w);
            if ($w === '') continue;
            $count += max(1, preg_match_all('/[aeiouy]+/u', $w));
        }
        return $count;
    }

    protected function tryParseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') return null;
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : null;
    }
}

