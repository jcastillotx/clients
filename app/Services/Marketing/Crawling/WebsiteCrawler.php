<?php

namespace App\Services\Marketing\Crawling;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebsiteCrawler
{
    /**
     * @param array{
     *   max_pages?:int,
     *   timeout_seconds?:int,
     *   respect_robots?:bool,
     *   user_agent?:string,
     * } $options
     * @return array{
     *   base_url:string,
     *   base_host:string,
     *   pages: array<int, array<string,mixed>>,
     *   graph: array{outbound: array<string, array<int,string>>, inbound: array<string, array<int,string>>},
     *   robots: array{fetched:bool, disallow:array<int,string>, sitemap:array<int,string>},
     * }
     */
    public function crawl(string $startUrl, array $options = []): array
    {
        $startUrl = $this->normalizeStartUrl($startUrl);
        $baseHost = (string) (parse_url($startUrl, PHP_URL_HOST) ?? '');
        $baseUrl = $this->origin($startUrl);

        $maxPages = max(1, (int) ($options['max_pages'] ?? 50));
        $timeoutSeconds = max(1, (int) ($options['timeout_seconds'] ?? 20));
        $respectRobots = (bool) ($options['respect_robots'] ?? true);
        $ua = (string) ($options['user_agent'] ?? 'Kre8ivDesigns-WebsiteAuditor/1.0');

        $robots = [
            'fetched' => false,
            'disallow' => [],
            'sitemap' => [],
        ];
        if ($respectRobots) {
            $robots = $this->fetchRobotsTxt($baseUrl, $timeoutSeconds, $ua);
        }

        $queue = [$startUrl];
        $visited = [];
        $pages = [];
        $outbound = [];
        $inbound = [];

        while (!empty($queue) && count($pages) < $maxPages) {
            $url = array_shift($queue);
            if (!is_string($url) || $url === '') {
                continue;
            }

            $url = $this->normalizeUrl($url);
            if ($url === null) {
                continue;
            }

            if (isset($visited[$url])) {
                continue;
            }
            $visited[$url] = true;

            if ($respectRobots && $this->isDisallowedByRobots($url, $robots['disallow'], $baseHost)) {
                // Skip crawl but keep graph placeholder.
                $outbound[$url] = $outbound[$url] ?? [];
                $inbound[$url] = $inbound[$url] ?? [];
                continue;
            }

            [$resp, $timing] = $this->fetchUrl($url, $timeoutSeconds, $ua);

            $status = $resp?->status() ?? 0;
            $body = $resp?->body() ?? '';
            $contentType = (string) ($resp?->header('Content-Type') ?? '');

            $page = [
                'url' => $url,
                'status_code' => $status,
                'load_time_ms' => $timing['total_ms'] ?? null,
                'ttfb_ms' => $timing['ttfb_ms'] ?? null,
                'page_size_kb' => $body !== '' ? (int) ceil(strlen($body) / 1024) : null,
                'content_type' => $contentType,
                'headers' => $resp?->headers() ?? [],
            ];

            if ($status >= 200 && $status < 400 && Str::contains(strtolower($contentType), 'text/html')) {
                $extract = $this->extractFromHtml($body, $url, $baseHost);
                $page = array_merge($page, $extract);

                $outLinks = (array) ($extract['links']['all'] ?? []);
                $outbound[$url] = array_values(array_unique(array_map('strval', $outLinks)));
                foreach ($outbound[$url] as $to) {
                    $inbound[$to] = $inbound[$to] ?? [];
                    $inbound[$to][] = $url;
                }

                $internal = (array) ($extract['links']['internal'] ?? []);
                foreach ($internal as $link) {
                    $link = (string) $link;
                    if ($link === '') continue;
                    if (!isset($visited[$link]) && count($queue) < ($maxPages * 10)) {
                        $queue[] = $link;
                    }
                }
            } else {
                $outbound[$url] = $outbound[$url] ?? [];
                $inbound[$url] = $inbound[$url] ?? [];
            }

            $pages[] = $page;
        }

        // De-duplicate inbound lists.
        foreach ($inbound as $k => $arr) {
            $inbound[$k] = array_values(array_unique(array_map('strval', (array) $arr)));
        }

        return [
            'base_url' => $baseUrl,
            'base_host' => $baseHost,
            'pages' => $pages,
            'graph' => [
                'outbound' => $outbound,
                'inbound' => $inbound,
            ],
            'robots' => $robots,
        ];
    }

    protected function normalizeStartUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') return null;
        if (!Str::startsWith($url, ['http://', 'https://'])) return null;

        $parts = parse_url($url);
        if (!is_array($parts)) return null;
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme === '' || $host === '') return null;

        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';

        // Normalize: remove fragment, keep query.
        $normalized = "{$scheme}://{$host}" . $path . $query;
        return rtrim($normalized, '#');
    }

    protected function origin(string $url): string
    {
        $parts = parse_url($url);
        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) ($parts['host'] ?? '');
        $port = $parts['port'] ?? null;

        if ($host === '') {
            return $url;
        }
        $isDefault = ($scheme === 'https' && (int) $port === 443) || ($scheme === 'http' && (int) $port === 80);
        $portPart = ($port && !$isDefault) ? (':' . (int) $port) : '';
        return "{$scheme}://{$host}{$portPart}";
    }

    /**
     * @return array{0:?Response,1:array{total_ms:?int,ttfb_ms:?int}}
     */
    protected function fetchUrl(string $url, int $timeoutSeconds, string $ua): array
    {
        $started = microtime(true);
        try {
            // Best-effort: TTFB isn't directly exposed by Laravel HTTP; we approximate it as ~25% of total for placeholder.
            $resp = Http::timeout($timeoutSeconds)
                ->withHeaders([
                    'User-Agent' => $ua,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            $totalMs = (int) round((microtime(true) - $started) * 1000);
            return [$resp, ['total_ms' => $totalMs, 'ttfb_ms' => (int) round($totalMs * 0.25)]];
        } catch (\Throwable) {
            $totalMs = (int) round((microtime(true) - $started) * 1000);
            return [null, ['total_ms' => $totalMs, 'ttfb_ms' => null]];
        }
    }

    /**
     * @return array{fetched:bool, disallow:array<int,string>, sitemap:array<int,string>}
     */
    protected function fetchRobotsTxt(string $baseUrl, int $timeoutSeconds, string $ua): array
    {
        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';
        try {
            $resp = Http::timeout($timeoutSeconds)
                ->withHeaders(['User-Agent' => $ua, 'Accept' => 'text/plain,*/*'])
                ->get($robotsUrl);
            if (!$resp->successful()) {
                return ['fetched' => true, 'disallow' => [], 'sitemap' => []];
            }

            $lines = preg_split('/\r\n|\r|\n/', (string) $resp->body()) ?: [];
            $disallow = [];
            $sitemaps = [];
            $inGlobal = false;

            foreach ($lines as $line) {
                $line = trim((string) $line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                $line = preg_replace('/\s+#.*$/', '', $line) ?? $line;

                if (Str::startsWith(strtolower($line), 'user-agent:')) {
                    $uaVal = trim(substr($line, strlen('user-agent:')));
                    $inGlobal = ($uaVal === '*' || $uaVal === '"*"');
                } elseif (Str::startsWith(strtolower($line), 'disallow:') && $inGlobal) {
                    $path = trim(substr($line, strlen('disallow:')));
                    if ($path !== '') {
                        $disallow[] = $path;
                    }
                } elseif (Str::startsWith(strtolower($line), 'sitemap:')) {
                    $sm = trim(substr($line, strlen('sitemap:')));
                    if ($sm !== '') {
                        $sitemaps[] = $sm;
                    }
                }
            }

            return [
                'fetched' => true,
                'disallow' => array_values(array_unique($disallow)),
                'sitemap' => array_values(array_unique($sitemaps)),
            ];
        } catch (\Throwable) {
            return ['fetched' => false, 'disallow' => [], 'sitemap' => []];
        }
    }

    /**
     * @param array<int,string> $disallow
     */
    protected function isDisallowedByRobots(string $url, array $disallow, string $baseHost): bool
    {
        if (empty($disallow)) return false;
        $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');
        if ($host === '' || strtolower($host) !== strtolower($baseHost)) return false;

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
        foreach ($disallow as $rule) {
            $rule = trim((string) $rule);
            if ($rule === '') continue;
            if ($rule === '/') return true;
            if (str_starts_with($path, $rule)) return true;
        }
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractFromHtml(string $html, string $pageUrl, string $baseHost): array
    {
        // Prefer Symfony DomCrawler if present, otherwise fallback to DOMDocument.
        if (class_exists(\Symfony\Component\DomCrawler\Crawler::class)) {
            return $this->extractWithSymfonyCrawler($html, $pageUrl, $baseHost);
        }
        return $this->extractWithDomDocument($html, $pageUrl, $baseHost);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractWithSymfonyCrawler(string $html, string $pageUrl, string $baseHost): array
    {
        $c = new \Symfony\Component\DomCrawler\Crawler($html, $pageUrl);

        $title = trim($c->filter('title')->count() ? (string) $c->filter('title')->text() : '');
        $description = trim($c->filter('meta[name="description"]')->count() ? (string) $c->filter('meta[name="description"]')->attr('content') : '');
        $canonical = trim($c->filter('link[rel="canonical"]')->count() ? (string) $c->filter('link[rel="canonical"]')->attr('href') : '');
        $viewport = trim($c->filter('meta[name="viewport"]')->count() ? (string) $c->filter('meta[name="viewport"]')->attr('content') : '');

        $headers = [];
        for ($i = 1; $i <= 6; $i++) {
            $headers["h{$i}"] = $c->filter("h{$i}")->each(fn ($n) => trim((string) $n->text()));
        }

        $links = $c->filter('a[href]')->each(fn ($n) => (string) $n->attr('href'));
        $resolvedLinks = $this->resolveAndClassifyLinks($pageUrl, $links, $baseHost);

        $images = $c->filter('img')->each(function ($n) {
            return [
                'src' => (string) $n->attr('src'),
                'alt' => (string) $n->attr('alt'),
            ];
        });

        $hasSchema = $c->filter('script[type="application/ld+json"]')->count() > 0;
        $h1 = (string) (($headers['h1'][0] ?? '') ?: '');
        $wordCount = $this->countWordsFromText($c->filter('body')->count() ? (string) $c->filter('body')->text() : '');

        $forms = $c->filter('form')->each(function ($n) {
            return [
                'action' => (string) $n->attr('action'),
                'method' => strtolower((string) ($n->attr('method') ?? 'get')),
            ];
        });

        return [
            'title' => $title !== '' ? $title : null,
            'meta_description' => $description !== '' ? $description : null,
            'has_canonical' => $canonical !== '',
            'canonical_url' => $canonical !== '' ? $this->resolveUrl($pageUrl, $canonical) : null,
            'has_schema' => $hasSchema,
            'mobile_viewport' => $viewport !== '' ? $viewport : null,
            'headers_structure' => $headers,
            'h1_tag' => $h1 !== '' ? $h1 : null,
            'word_count' => $wordCount ?: null,
            'links' => $resolvedLinks,
            'images' => [
                'items' => $this->resolveImages($pageUrl, $images),
            ],
            'forms' => $forms,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractWithDomDocument(string $html, string $pageUrl, string $baseHost): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xp = new \DOMXPath($doc);

        $title = trim((string) ($xp->evaluate('string(//title)') ?? ''));
        $description = trim((string) ($xp->evaluate('string(//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]/@content)') ?? ''));
        $canonical = trim((string) ($xp->evaluate('string(//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]/@href)') ?? ''));
        $viewport = trim((string) ($xp->evaluate('string(//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="viewport"]/@content)') ?? ''));

        $headers = [];
        for ($i = 1; $i <= 6; $i++) {
            $nodes = $xp->query("//h{$i}") ?: [];
            $vals = [];
            foreach ($nodes as $n) {
                $vals[] = trim((string) $n->textContent);
            }
            $headers["h{$i}"] = array_values(array_filter($vals, fn ($v) => $v !== ''));
        }

        $aNodes = $xp->query('//a[@href]') ?: [];
        $hrefs = [];
        foreach ($aNodes as $a) {
            $hrefs[] = (string) $a->getAttribute('href');
        }
        $resolvedLinks = $this->resolveAndClassifyLinks($pageUrl, $hrefs, $baseHost);

        $imgNodes = $xp->query('//img') ?: [];
        $imgs = [];
        foreach ($imgNodes as $img) {
            $imgs[] = [
                'src' => (string) $img->getAttribute('src'),
                'alt' => (string) $img->getAttribute('alt'),
            ];
        }

        $schemaNodes = $xp->query('//script[@type="application/ld+json"]') ?: [];
        $hasSchema = is_iterable($schemaNodes) && $schemaNodes->length > 0;

        $bodyText = trim((string) ($xp->evaluate('string(//body)') ?? ''));
        $wordCount = $this->countWordsFromText($bodyText);

        $formNodes = $xp->query('//form') ?: [];
        $forms = [];
        foreach ($formNodes as $f) {
            $method = strtolower((string) $f->getAttribute('method'));
            $forms[] = [
                'action' => (string) $f->getAttribute('action'),
                'method' => $method !== '' ? $method : 'get',
            ];
        }

        $h1 = (string) (($headers['h1'][0] ?? '') ?: '');

        return [
            'title' => $title !== '' ? $title : null,
            'meta_description' => $description !== '' ? $description : null,
            'has_canonical' => $canonical !== '',
            'canonical_url' => $canonical !== '' ? $this->resolveUrl($pageUrl, $canonical) : null,
            'has_schema' => $hasSchema,
            'mobile_viewport' => $viewport !== '' ? $viewport : null,
            'headers_structure' => $headers,
            'h1_tag' => $h1 !== '' ? $h1 : null,
            'word_count' => $wordCount ?: null,
            'links' => $resolvedLinks,
            'images' => [
                'items' => $this->resolveImages($pageUrl, $imgs),
            ],
            'forms' => $forms,
        ];
    }

    /**
     * @param array<int, string> $hrefs
     * @return array{all:array<int,string>, internal:array<int,string>, external:array<int,string>}
     */
    protected function resolveAndClassifyLinks(string $pageUrl, array $hrefs, string $baseHost): array
    {
        $all = [];
        $internal = [];
        $external = [];

        foreach ($hrefs as $href) {
            $href = trim((string) $href);
            if ($href === '' || str_starts_with($href, '#')) continue;
            if (Str::startsWith(strtolower($href), ['mailto:', 'tel:', 'javascript:'])) continue;

            $resolved = $this->resolveUrl($pageUrl, $href);
            if ($resolved === null) continue;

            $all[] = $resolved;
            $host = (string) (parse_url($resolved, PHP_URL_HOST) ?? '');
            if ($host !== '' && strtolower($host) === strtolower($baseHost)) {
                $internal[] = $resolved;
            } else {
                $external[] = $resolved;
            }
        }

        return [
            'all' => array_values(array_unique($all)),
            'internal' => array_values(array_unique($internal)),
            'external' => array_values(array_unique($external)),
        ];
    }

    /**
     * @param array<int, array{src:string,alt:string}> $imgs
     * @return array<int, array{src:?string,alt:?string,src_resolved:?string,has_alt:bool}>
     */
    protected function resolveImages(string $pageUrl, array $imgs): array
    {
        $out = [];
        foreach ($imgs as $img) {
            $src = trim((string) ($img['src'] ?? ''));
            $alt = (string) ($img['alt'] ?? '');
            $resolved = $src !== '' ? $this->resolveUrl($pageUrl, $src) : null;
            $out[] = [
                'src' => $src !== '' ? $src : null,
                'alt' => $alt !== '' ? $alt : null,
                'src_resolved' => $resolved,
                'has_alt' => trim($alt) !== '',
            ];
        }
        return $out;
    }

    protected function countWordsFromText(string $text): int
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') return 0;
        return count(preg_split('/\s+/', $text) ?: []);
    }

    protected function resolveUrl(string $baseUrl, string $href): ?string
    {
        $href = trim($href);
        if ($href === '') return null;
        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $this->normalizeUrl($href);
        }

        if (Str::startsWith($href, '//')) {
            $scheme = (string) (parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https');
            return $this->normalizeUrl($scheme . ':' . $href);
        }

        $baseParts = parse_url($baseUrl);
        if (!is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return null;
        }

        $scheme = (string) $baseParts['scheme'];
        $host = (string) $baseParts['host'];
        $port = $baseParts['port'] ?? null;
        $origin = $scheme . '://' . $host . (($port && (int) $port !== 80 && (int) $port !== 443) ? (':' . (int) $port) : '');

        if (Str::startsWith($href, '/')) {
            return $this->normalizeUrl($origin . $href);
        }

        $basePath = (string) ($baseParts['path'] ?? '/');
        $dir = Str::contains($basePath, '/') ? (string) Str::beforeLast($basePath, '/') : '';
        if ($dir === '') $dir = '/';
        $full = rtrim($origin . $dir, '/') . '/' . $href;
        return $this->normalizeUrl($this->removeDotSegments($full));
    }

    protected function removeDotSegments(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) return $url;
        $path = (string) ($parts['path'] ?? '/');
        $segments = explode('/', $path);
        $out = [];
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') {
                array_pop($out);
                continue;
            }
            $out[] = $seg;
        }
        $newPath = '/' . implode('/', $out);
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $newPath . $query;
    }
}

