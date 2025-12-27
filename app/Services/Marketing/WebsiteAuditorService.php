<?php

namespace App\Services\Marketing;

use App\Models\AuditHistory;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use App\Models\WebsiteAudit;
use App\Services\Marketing\Ai\WebsiteAuditAiInsightsService;
use App\Services\Marketing\Crawling\WebsiteCrawler;
use App\Services\Marketing\Scoring\WebsiteAuditScorer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebsiteAuditorService
{
    public function __construct(
        private readonly WebsiteCrawler $crawler,
        private readonly WebsiteAuditScorer $scorer,
        private readonly WebsiteAuditAiInsightsService $aiInsights,
    ) {
    }

    /**
     * Perform a comprehensive audit.
     *
     * @param array{
     *   client_id?:?int,
     *   audit_type?:'full'|'seo'|'performance'|'accessibility',
     *   max_pages?:int,
     *   respect_robots?:bool,
     *   use_ai?:bool,
     *   ai_provider?:string,
     *   ai_model?:?string,
     *   competitors?:array<int,string>,
     * } $options
     * @return array<string,mixed>
     */
    public function performFullAudit(string $url, array $options = []): array
    {
        $url = $this->normalizeStartUrl($url);
        $this->guardAgainstUnsafeUrl($url);
        $clientId = $options['client_id'] ?? null;
        $auditType = (string) ($options['audit_type'] ?? 'full');
        $maxPages = max(1, (int) ($options['max_pages'] ?? 50));
        $respectRobots = (bool) ($options['respect_robots'] ?? true);

        $audit = WebsiteAudit::create([
            'client_id' => $clientId,
            'website_url' => $url,
            'audit_type' => $auditType,
            'status' => 'running',
            'started_at' => now(),
            'meta' => [
                'max_pages' => $maxPages,
                'respect_robots' => $respectRobots,
            ],
        ]);

        try {
            $crawl = $this->crawler->crawl($url, [
                'max_pages' => $maxPages,
                'respect_robots' => $respectRobots,
            ]);

            $seo = $this->technicalSEOAudit($url, $crawl);
            $perf = $this->performanceAudit($url, $crawl);
            $security = $this->securityAudit($url, $crawl);
            $mobile = $this->mobileAudit($url, $crawl, $perf);
            $accessibility = $this->accessibilityAudit($url, $crawl, $perf);

            $issues = array_merge(
                (array) ($seo['issues'] ?? []),
                (array) ($perf['issues'] ?? []),
                (array) ($security['issues'] ?? []),
                (array) ($mobile['issues'] ?? []),
                (array) ($accessibility['issues'] ?? []),
            );

            $scores = $this->scorer->score($issues, [
                'lcp_ms' => data_get($perf, 'metrics.cwv.lcp_ms'),
                'cls' => data_get($perf, 'metrics.cwv.cls'),
                'ttfb_ms' => data_get($perf, 'metrics.ttfb_ms'),
                'mobile_friendly' => data_get($mobile, 'metrics.mobile_friendly'),
            ]);

            $report = [
                'meta' => [
                    'website_url' => $url,
                    'audit_id' => $audit->id,
                    'audited_at' => now()->toIso8601String(),
                    'max_pages' => $maxPages,
                    'pages_crawled' => count((array) ($crawl['pages'] ?? [])),
                    'respect_robots' => $respectRobots,
                    'api_sources' => $this->apiSourcesUsed($perf),
                ],
                'scores' => $scores,
                'seo' => $seo,
                'performance' => $perf,
                'security' => $security,
                'mobile' => $mobile,
                'accessibility' => $accessibility,
                'issues' => $this->sortIssuesForReport($issues),
                'competitors' => is_array($options['competitors'] ?? null) ? (array) $options['competitors'] : [],
            ];

            // Persist pages + issues
            $this->persistPagesAndIssues($audit, $crawl, $report);

            // AI recommendations / roadmap (optional)
            if ((bool) ($options['use_ai'] ?? true)) {
                try {
                    $ai = $this->aiInsights->generate($report, [
                        'client_id' => $clientId,
                        'preferred_provider' => (string) ($options['ai_provider'] ?? 'claude'),
                        'model' => $options['ai_model'] ?? null,
                    ]);
                    $report['ai'] = $ai;
                } catch (\Throwable $e) {
                    $report['ai'] = [
                        'summary' => '',
                        'recommendations' => [],
                        'roadmap' => [],
                        'roi' => [],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $audit->update([
                'status' => 'completed',
                'completed_at' => now(),
                'score' => (int) ($scores['overall'] ?? null),
                'scores' => $scores,
                'report' => $report,
                'meta' => array_merge((array) ($audit->meta ?? []), [
                    'crawled_pages' => count((array) ($crawl['pages'] ?? [])),
                ]),
                'failure_reason' => null,
            ]);

            $this->writeHistoryRow($audit, $report);

            return $report;
        } catch (\Throwable $e) {
            Log::warning('Website audit failed', [
                'audit_id' => $audit->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            $audit->update([
                'status' => 'failed',
                'completed_at' => now(),
                'failure_reason' => $e->getMessage(),
            ]);

            return [
                'meta' => [
                    'website_url' => $url,
                    'audit_id' => $audit->id,
                    'status' => 'failed',
                ],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Technical SEO audit.
     *
     * @param array<string,mixed>|null $crawl
     * @return array<string,mixed>
     */
    public function technicalSEOAudit(string $url, ?array $crawl = null): array
    {
        $crawl ??= $this->crawler->crawl($url, [
            'max_pages' => (int) config('website-auditor.crawl.max_pages', 50),
            'respect_robots' => (bool) config('website-auditor.crawl.respect_robots', true),
        ]);

        $pages = (array) ($crawl['pages'] ?? []);
        $issues = [];

        // robots.txt and sitemap.xml
        $robots = (array) ($crawl['robots'] ?? []);
        if (($robots['fetched'] ?? false) === false) {
            $issues[] = $this->issue('warning', 'seo', 'robots_txt_unreachable', 'robots.txt could not be fetched (best-effort).', $url, 'Ensure robots.txt is accessible and valid.');
        }

        $sitemapUrls = (array) ($robots['sitemap'] ?? []);
        if (empty($sitemapUrls)) {
            // Try default location
            $sitemapUrls = [rtrim($this->origin($url), '/') . '/sitemap.xml'];
        }

        $sitemap = $this->fetchSitemapUrls($sitemapUrls);
        if (($sitemap['fetched'] ?? false) === false) {
            $issues[] = $this->issue('warning', 'seo', 'sitemap_missing', 'sitemap.xml was not found or could not be fetched.', $url, 'Add a sitemap.xml and reference it in robots.txt.');
        }

        // Page-level checks
        $missingTitle = 0;
        $missingDesc = 0;
        $missingH1 = 0;
        $missingCanonical = 0;
        $missingSchema = 0;
        $imagesMissingAlt = 0;

        $titles = [];
        $descs = [];
        $contentHashes = [];
        $duplicates = [];

        foreach ($pages as $p) {
            $pageUrl = (string) ($p['url'] ?? '');
            if ($pageUrl === '') continue;
            $status = (int) ($p['status_code'] ?? 0);
            if ($status >= 400) continue;

            $title = trim((string) ($p['title'] ?? ''));
            $desc = trim((string) ($p['meta_description'] ?? ''));
            $h1 = trim((string) ($p['h1_tag'] ?? ''));
            $hasCanonical = (bool) ($p['has_canonical'] ?? false);
            $hasSchema = (bool) ($p['has_schema'] ?? false);

            if ($title === '') $missingTitle++;
            if ($desc === '') $missingDesc++;
            if ($h1 === '') $missingH1++;
            if (!$hasCanonical) $missingCanonical++;
            if (!$hasSchema) $missingSchema++;

            if ($title !== '') {
                $titles[$title][] = $pageUrl;
            }
            if ($desc !== '') {
                $descs[$desc][] = $pageUrl;
            }

            $text = $this->normalizeTextForHash((string) ($p['title'] ?? '') . "\n" . (string) ($p['meta_description'] ?? '') . "\n" . json_encode($p['headers_structure'] ?? [], JSON_UNESCAPED_SLASHES));
            $hash = $text !== '' ? sha1($text) : null;
            if ($hash) {
                if (isset($contentHashes[$hash])) {
                    $duplicates[$hash] = array_values(array_unique(array_merge((array) ($duplicates[$hash] ?? [$contentHashes[$hash]]), [$pageUrl])));
                } else {
                    $contentHashes[$hash] = $pageUrl;
                }
            }

            $imgs = (array) data_get($p, 'images.items', []);
            foreach ($imgs as $img) {
                $hasAlt = (bool) ($img['has_alt'] ?? false);
                if (!$hasAlt) $imagesMissingAlt++;
            }
        }

        if ($missingTitle > 0) {
            $issues[] = $this->issue('critical', 'seo', 'missing_title_tags', "{$missingTitle} page(s) missing <title>.", null, 'Add unique, descriptive title tags for every indexable page.', ['scope' => 'many_pages']);
        }
        if ($missingDesc > 0) {
            $issues[] = $this->issue('error', 'seo', 'missing_meta_descriptions', "{$missingDesc} page(s) missing meta description.", null, 'Add unique meta descriptions (aim 120–160 chars) for important pages.', ['scope' => 'many_pages']);
        }
        if ($missingH1 > 0) {
            $issues[] = $this->issue('warning', 'seo', 'missing_h1', "{$missingH1} page(s) missing H1.", null, 'Ensure each page has one clear H1 describing the page topic.', ['scope' => 'many_pages']);
        }
        if ($missingCanonical > 0) {
            $issues[] = $this->issue('warning', 'seo', 'missing_canonical', "{$missingCanonical} page(s) missing canonical tags.", null, 'Add canonical tags to avoid duplicate content issues.', ['scope' => 'many_pages']);
        }
        if ($missingSchema > 0) {
            $issues[] = $this->issue('info', 'seo', 'missing_schema_markup', "{$missingSchema} page(s) missing schema markup (LD+JSON).", null, 'Add structured data where appropriate (Organization, Article, Product, FAQ, etc.).');
        }
        if ($imagesMissingAlt > 0) {
            $issues[] = $this->issue('high', 'seo', 'missing_image_alt_text', "{$imagesMissingAlt} image(s) missing alt text.", null, 'Add descriptive alt text to images (especially functional/CTA images).', ['scope' => 'many_pages']);
        }

        // Duplicate titles / descriptions
        foreach ($titles as $t => $urls) {
            if (count($urls) >= 2) {
                $issues[] = $this->issue('high', 'seo', 'duplicate_title', 'Duplicate title detected across multiple pages.', (string) ($urls[0] ?? null), 'Ensure every page has a unique title tag.', ['examples' => $urls]);
                break;
            }
        }
        foreach ($descs as $d => $urls) {
            if (count($urls) >= 2) {
                $issues[] = $this->issue('medium', 'seo', 'duplicate_meta_description', 'Duplicate meta description detected across multiple pages.', (string) ($urls[0] ?? null), 'Ensure meta descriptions are unique for key pages.', ['examples' => $urls]);
                break;
            }
        }
        foreach ($duplicates as $hash => $urls) {
            if (count($urls) >= 2) {
                $issues[] = $this->issue('high', 'seo', 'duplicate_content_signature', 'Potential duplicate content across pages (heuristic).', (string) ($urls[0] ?? null), 'Consolidate or differentiate these pages (content/canonical/redirect).', ['examples' => $urls]);
                break;
            }
        }

        // Broken links: best-effort limited check
        $broken = $this->checkBrokenInternalLinks($crawl, limit: (int) config('website-auditor.crawl.max_link_checks', 200));
        if ($broken['count'] > 0) {
            $issues[] = $this->issue('critical', 'seo', 'broken_internal_links', "{$broken['count']} broken internal link(s) detected (best-effort sample).", null, 'Fix 404s (redirect, update links, or restore pages).', ['examples' => $broken['examples'], 'scope' => 'many_pages']);
        }

        // Basic SSL check (SEO signal)
        $ssl = $this->sslCertificateCheck($url);
        if (($ssl['ok'] ?? false) === false) {
            $issues[] = $this->issue('critical', 'security', 'ssl_invalid', 'SSL certificate is missing/invalid/expired (best-effort check).', $url, 'Fix TLS/SSL certificate and ensure HTTPS works site-wide.');
        }

        return [
            'metrics' => [
                'pages_scanned' => count($pages),
                'robots' => $robots,
                'sitemap' => $sitemap,
                'broken_links' => $broken,
                'ssl' => $ssl,
            ],
            'issues' => $this->normalizeIssueSeverities($issues),
        ];
    }

    /**
     * Performance audit.
     *
     * @param array<string,mixed>|null $crawl
     * @return array<string,mixed>
     */
    public function performanceAudit(string $url, ?array $crawl = null): array
    {
        $crawl ??= $this->crawler->crawl($url, [
            'max_pages' => (int) config('website-auditor.crawl.max_pages', 25),
            'respect_robots' => (bool) config('website-auditor.crawl.respect_robots', true),
        ]);

        $pages = (array) ($crawl['pages'] ?? []);
        $issues = [];

        // Simple derived metrics from crawl.
        $loadTimes = [];
        $sizes = [];
        $requests = []; // not available from server-side crawl; placeholder
        $ttfb = [];
        foreach ($pages as $p) {
            if (($p['status_code'] ?? 0) < 200 || ($p['status_code'] ?? 0) >= 400) continue;
            if (isset($p['load_time_ms'])) $loadTimes[] = (int) $p['load_time_ms'];
            if (isset($p['page_size_kb'])) $sizes[] = (int) $p['page_size_kb'];
            if (isset($p['ttfb_ms'])) $ttfb[] = (int) $p['ttfb_ms'];
        }

        $avgLoad = !empty($loadTimes) ? (int) round(array_sum($loadTimes) / count($loadTimes)) : null;
        $p95Load = $this->percentile($loadTimes, 95);
        $avgTtfb = !empty($ttfb) ? (int) round(array_sum($ttfb) / count($ttfb)) : null;
        $avgSize = !empty($sizes) ? (int) round(array_sum($sizes) / count($sizes)) : null;

        if ($avgLoad !== null && $avgLoad > 2500) {
            $issues[] = $this->issue('critical', 'performance', 'slow_load_time', "Average page load time is high (~{$avgLoad}ms).", null, 'Optimize server response, caching, images, and render-blocking assets.', ['scope' => 'many_pages']);
        }
        if ($avgTtfb !== null && $avgTtfb > 800) {
            $issues[] = $this->issue('high', 'performance', 'slow_ttfb', "TTFB is high (~{$avgTtfb}ms, approximate).", null, 'Improve server performance: caching, DB optimization, CDN, and backend profiling.');
        }

        // Google PageSpeed Insights (optional)
        $psi = $this->googlePageSpeedInsights($url);
        if (($psi['available'] ?? false) === false) {
            $issues[] = $this->issue('info', 'performance', 'pagespeed_api_not_configured', 'Google PageSpeed Insights API not configured; using crawl-based approximations.', $url, 'Set GOOGLE_PAGESPEED_API_KEY to enable Lighthouse metrics (FCP/LCP/CLS/FID).');
        }

        $cwv = $this->extractCwvFromPsi($psi);
        if (($cwv['lcp_ms'] ?? null) !== null && (int) $cwv['lcp_ms'] > 4000) {
            $issues[] = $this->issue('critical', 'performance', 'lcp_poor', 'Largest Contentful Paint (LCP) is poor.', $url, 'Optimize LCP element (hero image, server response, preload critical resources).', ['lcp_ms' => $cwv['lcp_ms']]);
        }
        if (($cwv['cls'] ?? null) !== null && (float) $cwv['cls'] > 0.25) {
            $issues[] = $this->issue('high', 'performance', 'cls_poor', 'Cumulative Layout Shift (CLS) is poor.', $url, 'Reserve space for images/ads, avoid late-injected banners, stabilize fonts.', ['cls' => $cwv['cls']]);
        }

        // Image optimization heuristics
        $largeImgs = $this->findLargeImagesHeuristic($crawl);
        if ($largeImgs['count'] > 0) {
            $issues[] = $this->issue('high', 'performance', 'large_images', "{$largeImgs['count']} large image(s) detected (heuristic).", null, 'Compress and serve responsive images (WebP/AVIF, srcset).', ['examples' => $largeImgs['examples'], 'scope' => 'many_pages']);
        }

        // Caching/CDN detection (best-effort via headers on first page)
        $firstHeaders = !empty($pages) && is_array($pages[0]) ? (array) ($pages[0]['headers'] ?? []) : [];
        $cdn = $this->detectCdnFromHeaders($firstHeaders);
        $cache = $this->cacheSignalsFromHeaders($firstHeaders);
        if (($cache['has_cache_control'] ?? false) === false) {
            $issues[] = $this->issue('medium', 'performance', 'missing_cache_control', 'Cache-Control headers not detected on sample page.', $url, 'Add proper Cache-Control headers for static assets and HTML where appropriate.');
        }

        return [
            'metrics' => [
                'load_time_ms_avg' => $avgLoad,
                'load_time_ms_p95' => $p95Load,
                'ttfb_ms' => $avgTtfb,
                'total_page_size_kb_avg' => $avgSize,
                'num_requests' => !empty($requests) ? (int) round(array_sum($requests) / count($requests)) : null,
                'cwv' => $cwv,
                'psi' => $psi,
                'cache' => $cache,
                'cdn' => $cdn,
            ],
            'issues' => $this->normalizeIssueSeverities($issues),
        ];
    }

    /**
     * @param array<string,mixed> $crawl
     * @return array<string,mixed>
     */
    protected function securityAudit(string $url, array $crawl): array
    {
        $issues = [];
        $pages = (array) ($crawl['pages'] ?? []);
        $firstHeaders = !empty($pages) && is_array($pages[0]) ? (array) ($pages[0]['headers'] ?? []) : [];

        $ssl = $this->sslCertificateCheck($url);
        if (($ssl['ok'] ?? false) === false) {
            $issues[] = $this->issue('critical', 'security', 'ssl_invalid', 'SSL certificate is missing/invalid/expired (best-effort check).', $url, 'Fix TLS/SSL certificate and redirect all HTTP to HTTPS.');
        }

        $sec = $this->securityHeadersFromResponseHeaders($firstHeaders);
        foreach ($sec['missing'] as $missing) {
            $issues[] = $this->issue('medium', 'security', 'missing_security_header', "Missing security header: {$missing}.", $url, "Add {$missing} header to harden security.");
        }

        if (($sec['has_hsts'] ?? false) === false && Str::startsWith($url, 'https://')) {
            $issues[] = $this->issue('medium', 'security', 'missing_hsts', 'HSTS header not detected.', $url, 'Add Strict-Transport-Security (HSTS) to enforce HTTPS.');
        }

        return [
            'metrics' => [
                'ssl' => $ssl,
                'security_headers' => $sec,
            ],
            'issues' => $this->normalizeIssueSeverities($issues),
        ];
    }

    /**
     * @param array<string,mixed> $perf
     * @return array<string,mixed>
     */
    protected function mobileAudit(string $url, array $crawl, array $perf): array
    {
        $issues = [];
        $pages = (array) ($crawl['pages'] ?? []);

        $viewportMissing = 0;
        foreach ($pages as $p) {
            if (($p['status_code'] ?? 0) >= 400) continue;
            $vp = trim((string) ($p['mobile_viewport'] ?? ''));
            if ($vp === '') $viewportMissing++;
        }

        if ($viewportMissing > 0) {
            $issues[] = $this->issue('critical', 'mobile', 'missing_viewport_meta', "{$viewportMissing} page(s) missing viewport meta tag.", null, 'Add <meta name="viewport" content="width=device-width, initial-scale=1">.', ['scope' => 'many_pages']);
        }

        $psiMobile = data_get($perf, 'metrics.psi.mobile');
        $mobileFriendly = null;
        if (is_array($psiMobile) && isset($psiMobile['lighthouse_categories']['performance_score'])) {
            // heuristic: if PSI mobile exists, assume mobile-friendly unless flagged otherwise
            $mobileFriendly = true;
        }
        if ($viewportMissing > 0) {
            $mobileFriendly = false;
        }

        return [
            'metrics' => [
                'mobile_friendly' => $mobileFriendly,
                'viewport_missing_pages' => $viewportMissing,
            ],
            'issues' => $this->normalizeIssueSeverities($issues),
        ];
    }

    /**
     * @param array<string,mixed> $perf
     * @return array<string,mixed>
     */
    protected function accessibilityAudit(string $url, array $crawl, array $perf): array
    {
        $issues = [];
        $pages = (array) ($crawl['pages'] ?? []);

        $missingAltImgs = 0;
        foreach ($pages as $p) {
            $imgs = (array) data_get($p, 'images.items', []);
            foreach ($imgs as $img) {
                if (!(bool) ($img['has_alt'] ?? false)) $missingAltImgs++;
            }
        }
        if ($missingAltImgs > 0) {
            $issues[] = $this->issue('high', 'accessibility', 'images_missing_alt', "{$missingAltImgs} image(s) missing alt text.", null, 'Add alt attributes to images; use empty alt="" for decorative images.', ['scope' => 'many_pages']);
        }

        $psi = data_get($perf, 'metrics.psi');
        $a11y = null;
        if (is_array($psi) && isset($psi['mobile']['lighthouse_categories']['accessibility_score'])) {
            $a11y = (int) $psi['mobile']['lighthouse_categories']['accessibility_score'];
            if ($a11y < 80) {
                $issues[] = $this->issue('medium', 'accessibility', 'low_lighthouse_accessibility_score', "Lighthouse accessibility score is low ({$a11y}).", $url, 'Fix Lighthouse accessibility findings (contrast, labels, landmarks, focus states).');
            }
        }

        return [
            'metrics' => [
                'lighthouse_accessibility_score' => $a11y,
            ],
            'issues' => $this->normalizeIssueSeverities($issues),
        ];
    }

    /**
     * Persist normalized pages and issues into DB tables.
     *
     * @param array<string,mixed> $crawl
     * @param array<string,mixed> $report
     */
    protected function persistPagesAndIssues(WebsiteAudit $audit, array $crawl, array $report): void
    {
        $audit->issues()->delete();
        $audit->pages()->delete();

        $pages = (array) ($crawl['pages'] ?? []);
        foreach ($pages as $p) {
            $url = (string) ($p['url'] ?? '');
            if ($url === '') continue;

            AuditPage::create([
                'website_audit_id' => $audit->id,
                'url' => $url,
                'title' => $p['title'] ?? null,
                'meta_description' => $p['meta_description'] ?? null,
                'h1_tag' => $p['h1_tag'] ?? null,
                'word_count' => $p['word_count'] ?? null,
                'load_time_ms' => $p['load_time_ms'] ?? null,
                'page_size_kb' => $p['page_size_kb'] ?? null,
                'status_code' => $p['status_code'] ?? null,
                'has_canonical' => (bool) ($p['has_canonical'] ?? false),
                'has_schema' => (bool) ($p['has_schema'] ?? false),
                'mobile_friendly' => $p['mobile_viewport'] ? true : null,
                'headers' => $p['headers_structure'] ?? null,
                'links' => $p['links'] ?? null,
                'images' => $p['images'] ?? null,
            ]);
        }

        $issues = (array) ($report['issues'] ?? []);
        foreach ($issues as $issue) {
            if (!is_array($issue)) continue;
            $row = $this->normalizeIssueForDb($issue);
            AuditIssue::create([
                'website_audit_id' => $audit->id,
                ...$row,
            ]);
        }
    }

    protected function writeHistoryRow(WebsiteAudit $audit, array $report): void
    {
        $scores = (array) ($audit->scores ?? []);

        AuditHistory::create([
            'client_id' => $audit->client_id,
            'website_url' => $audit->website_url,
            'audit_date' => now()->toDateString(),
            'overall_score' => (int) ($scores['overall'] ?? 0),
            'seo_score' => (int) ($scores['seo'] ?? 0),
            'performance_score' => (int) ($scores['performance'] ?? 0),
            'accessibility_score' => (int) ($scores['accessibility'] ?? 0),
            'total_issues' => count((array) ($report['issues'] ?? [])),
            'critical_issues' => (int) collect((array) ($report['issues'] ?? []))->where('severity', 'critical')->count(),
            'pages_crawled' => (int) data_get($report, 'meta.pages_crawled', 0),
        ]);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    protected function sortIssuesForReport(array $issues): array
    {
        $rank = ['critical' => 0, 'high' => 1, 'error' => 1, 'medium' => 2, 'warning' => 3, 'low' => 4, 'info' => 5];
        usort($issues, function ($a, $b) use ($rank) {
            $sa = (string) ($a['severity'] ?? 'info');
            $sb = (string) ($b['severity'] ?? 'info');
            $ra = $rank[$sa] ?? 99;
            $rb = $rank[$sb] ?? 99;
            if ($ra !== $rb) return $ra <=> $rb;
            return strcmp((string) ($a['category'] ?? ''), (string) ($b['category'] ?? ''));
        });
        return $issues;
    }

    /**
     * Normalize severity labels to spec: critical,error,warning,info (plus internal high/medium/low).
     *
     * @param array<int, array<string,mixed>> $issues
     * @return array<int, array<string,mixed>>
     */
    protected function normalizeIssueSeverities(array $issues): array
    {
        foreach ($issues as &$i) {
            $sev = (string) ($i['severity'] ?? 'info');
            // allow "high/medium/low" but map to spec equivalents as well
            $i['severity'] = match ($sev) {
                'high' => 'error',
                'medium' => 'warning',
                'low' => 'info',
                default => $sev,
            };
            $i['priority_score'] = $this->priorityScore($sev);
        }
        unset($i);
        return $issues;
    }

    protected function priorityScore(string $severity): int
    {
        return match ($severity) {
            'critical' => 95,
            'high', 'error' => 80,
            'medium', 'warning' => 55,
            'low', 'info' => 25,
            default => 40,
        };
    }

    /**
     * @param array<string,mixed> $issue
     * @return array{severity:string, category:string, issue_type:string, description:string, affected_url:?string, recommendation:?string, priority_score:?int, meta:?array}
     */
    protected function normalizeIssueForDb(array $issue): array
    {
        $meta = is_array($issue['meta'] ?? null) ? (array) $issue['meta'] : null;
        $severity = (string) ($issue['severity'] ?? 'info');
        $severity = match ($severity) {
            'high' => 'error',
            'medium' => 'warning',
            'low' => 'info',
            default => $severity,
        };

        return [
            'severity' => $severity,
            'category' => (string) ($issue['category'] ?? 'seo'),
            'issue_type' => (string) ($issue['issue_type'] ?? 'unknown'),
            'description' => (string) ($issue['description'] ?? ''),
            'affected_url' => isset($issue['affected_url']) ? (string) $issue['affected_url'] : null,
            'recommendation' => isset($issue['recommendation']) ? (string) $issue['recommendation'] : null,
            'priority_score' => isset($issue['priority_score']) ? (int) $issue['priority_score'] : null,
            'meta' => $meta,
        ];
    }

    /**
     * @param array<string, mixed> $perf
     * @return array<int, string>
     */
    protected function apiSourcesUsed(array $perf): array
    {
        $sources = [];
        if (data_get($perf, 'metrics.psi.available')) $sources[] = 'google_pagespeed_insights';
        return $sources;
    }

    protected function issue(string $severity, string $category, string $type, string $description, ?string $affectedUrl, string $recommendation, array $meta = []): array
    {
        return [
            'severity' => $severity,
            'category' => $category,
            'issue_type' => $type,
            'description' => $description,
            'affected_url' => $affectedUrl,
            'recommendation' => $recommendation,
            'meta' => $meta,
        ];
    }

    protected function normalizeStartUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return $url;
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    /**
     * Best-effort SSRF/unsafe URL guard for audits.
     *
     * @throws \InvalidArgumentException
     */
    protected function guardAgainstUnsafeUrl(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new \InvalidArgumentException('Invalid URL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new \InvalidArgumentException('URL must be http(s) with a valid host.');
        }

        $hostLower = strtolower($host);
        if (in_array($hostLower, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new \InvalidArgumentException('Refusing to audit local/loopback hosts.');
        }

        // If the host is an IP, block private/reserved ranges.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateOrReservedIp($host)) {
                throw new \InvalidArgumentException('Refusing to audit private/reserved IP ranges.');
            }
            return;
        }

        // Best-effort DNS resolve and check first A record.
        $records = @dns_get_record($host, DNS_A);
        if (is_array($records) && !empty($records)) {
            $ip = (string) ($records[0]['ip'] ?? '');
            if ($ip !== '' && $this->isPrivateOrReservedIp($ip)) {
                throw new \InvalidArgumentException('Refusing to audit hosts resolving to private/reserved IP ranges.');
            }
        }
    }

    protected function isPrivateOrReservedIp(string $ip): bool
    {
        // FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE returns false when IP is private/reserved.
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    protected function origin(string $url): string
    {
        $parts = parse_url($url);
        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) ($parts['host'] ?? '');
        $port = $parts['port'] ?? null;
        if ($host === '') return $url;
        $isDefault = ($scheme === 'https' && (int) $port === 443) || ($scheme === 'http' && (int) $port === 80);
        $portPart = ($port && !$isDefault) ? (':' . (int) $port) : '';
        return "{$scheme}://{$host}{$portPart}";
    }

    /**
     * @param array<string,mixed> $crawl
     * @return array{count:int, examples:array<int,array{from:string,to:string,status:?int}>}
     */
    protected function checkBrokenInternalLinks(array $crawl, int $limit = 200): array
    {
        $pages = (array) ($crawl['pages'] ?? []);
        $baseHost = (string) ($crawl['base_host'] ?? '');
        $checks = 0;
        $broken = [];

        foreach ($pages as $p) {
            $from = (string) ($p['url'] ?? '');
            $internal = (array) data_get($p, 'links.internal', []);
            foreach ($internal as $to) {
                if ($checks >= $limit) break 2;
                $to = (string) $to;
                if ($to === '') continue;
                $host = (string) (parse_url($to, PHP_URL_HOST) ?? '');
                if ($host === '' || strtolower($host) !== strtolower($baseHost)) continue;

                $checks++;
                try {
                    $resp = Http::timeout(10)
                        ->withHeaders(['User-Agent' => 'Kre8ivDesigns-WebsiteAuditor/1.0'])
                        ->head($to);
                    $status = $resp->status();
                    if ($status === 404 || $status === 410) {
                        $broken[] = ['from' => $from, 'to' => $to, 'status' => $status];
                    }
                } catch (\Throwable) {
                    $broken[] = ['from' => $from, 'to' => $to, 'status' => null];
                }
            }
        }

        return [
            'count' => count($broken),
            'examples' => array_slice($broken, 0, 20),
        ];
    }

    /**
     * @param array<int,string> $sitemapUrls
     * @return array{fetched:bool, urls:array<int,string>}
     */
    protected function fetchSitemapUrls(array $sitemapUrls): array
    {
        foreach ($sitemapUrls as $u) {
            $u = trim((string) $u);
            if ($u === '') continue;
            try {
                $resp = Http::timeout(15)->get($u);
                if (!$resp->successful()) continue;
                $xml = (string) $resp->body();
                $urls = $this->extractLocFromSitemapXml($xml);
                return ['fetched' => true, 'urls' => $urls];
            } catch (\Throwable) {
                continue;
            }
        }
        return ['fetched' => false, 'urls' => []];
    }

    /**
     * @return array<int,string>
     */
    protected function extractLocFromSitemapXml(string $xml): array
    {
        $urls = [];
        try {
            $sx = @simplexml_load_string($xml);
            if ($sx === false) return [];
            $locs = $sx->xpath('//url/loc') ?: [];
            foreach ($locs as $loc) {
                $u = trim((string) $loc);
                if ($u !== '') $urls[] = $u;
            }
        } catch (\Throwable) {
            return [];
        }
        return array_values(array_unique($urls));
    }

    /**
     * @return array{ok:bool, expires_at:?string, issuer:?string, subject:?string}
     */
    protected function sslCertificateCheck(string $url): array
    {
        $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');
        $scheme = (string) (parse_url($url, PHP_URL_SCHEME) ?? '');
        if ($host === '' || strtolower($scheme) !== 'https') {
            return ['ok' => false, 'expires_at' => null, 'issuer' => null, 'subject' => null];
        }

        try {
            $ctx = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'SNI_enabled' => true,
                    'peer_name' => $host,
                ],
            ]);

            $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
            if (!$client) {
                return ['ok' => false, 'expires_at' => null, 'issuer' => null, 'subject' => null];
            }

            $params = stream_context_get_params($client);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            if (!$cert) {
                return ['ok' => false, 'expires_at' => null, 'issuer' => null, 'subject' => null];
            }

            $parsed = openssl_x509_parse($cert);
            if (!is_array($parsed)) {
                return ['ok' => false, 'expires_at' => null, 'issuer' => null, 'subject' => null];
            }

            $validTo = isset($parsed['validTo_time_t']) ? (int) $parsed['validTo_time_t'] : null;
            $expiresAt = $validTo ? gmdate('c', $validTo) : null;
            $ok = $validTo ? ($validTo > time()) : false;

            return [
                'ok' => $ok,
                'expires_at' => $expiresAt,
                'issuer' => isset($parsed['issuer']) ? json_encode($parsed['issuer']) : null,
                'subject' => isset($parsed['subject']) ? json_encode($parsed['subject']) : null,
            ];
        } catch (\Throwable) {
            return ['ok' => false, 'expires_at' => null, 'issuer' => null, 'subject' => null];
        }
    }

    /**
     * @param array<string, array<int,string>> $headers
     * @return array{missing:array<int,string>, present:array<int,string>, has_hsts:bool}
     */
    protected function securityHeadersFromResponseHeaders(array $headers): array
    {
        $flat = [];
        foreach ($headers as $k => $vals) {
            $flat[strtolower((string) $k)] = is_array($vals) ? implode(', ', $vals) : (string) $vals;
        }

        $required = [
            'x-content-type-options',
            'x-frame-options',
            'referrer-policy',
            'content-security-policy',
        ];

        $present = [];
        $missing = [];
        foreach ($required as $h) {
            if (isset($flat[$h]) && trim((string) $flat[$h]) !== '') {
                $present[] = $h;
            } else {
                $missing[] = $h;
            }
        }

        $hasHsts = isset($flat['strict-transport-security']) && trim((string) $flat['strict-transport-security']) !== '';

        return [
            'missing' => $missing,
            'present' => $present,
            'has_hsts' => $hasHsts,
        ];
    }

    /**
     * @param array<string, array<int,string>> $headers
     * @return array{has_cache_control:bool, cache_control:?string, etag:bool}
     */
    protected function cacheSignalsFromHeaders(array $headers): array
    {
        $cc = null;
        $etag = false;
        foreach ($headers as $k => $vals) {
            $lk = strtolower((string) $k);
            if ($lk === 'cache-control') {
                $cc = is_array($vals) ? implode(', ', $vals) : (string) $vals;
            }
            if ($lk === 'etag') {
                $etag = true;
            }
        }

        return [
            'has_cache_control' => is_string($cc) && trim($cc) !== '',
            'cache_control' => $cc,
            'etag' => $etag,
        ];
    }

    /**
     * @param array<string, array<int,string>> $headers
     * @return array{detected:bool, provider:?string, evidence:?string}
     */
    protected function detectCdnFromHeaders(array $headers): array
    {
        $flat = [];
        foreach ($headers as $k => $vals) {
            $flat[strtolower((string) $k)] = is_array($vals) ? implode(', ', $vals) : (string) $vals;
        }

        $server = strtolower((string) ($flat['server'] ?? ''));
        $via = strtolower((string) ($flat['via'] ?? ''));
        $cf = strtolower((string) ($flat['cf-ray'] ?? $flat['cf-cache-status'] ?? ''));

        if ($cf !== '') {
            return ['detected' => true, 'provider' => 'cloudflare', 'evidence' => 'cf-* headers'];
        }
        if (str_contains($server, 'cloudfront') || str_contains($via, 'cloudfront')) {
            return ['detected' => true, 'provider' => 'cloudfront', 'evidence' => 'server/via'];
        }
        if (str_contains($server, 'fastly') || str_contains($via, 'fastly')) {
            return ['detected' => true, 'provider' => 'fastly', 'evidence' => 'server/via'];
        }
        return ['detected' => false, 'provider' => null, 'evidence' => null];
    }

    /**
     * @return array<string,mixed>
     */
    protected function googlePageSpeedInsights(string $url): array
    {
        $key = (string) config('website-auditor.integrations.google_pagespeed.api_key', '');
        if ($key === '') {
            return ['available' => false];
        }

        $out = [
            'available' => true,
            'mobile' => null,
            'desktop' => null,
        ];

        foreach (['mobile', 'desktop'] as $strategy) {
            try {
                $endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
                $categories = ['PERFORMANCE', 'ACCESSIBILITY', 'SEO', 'BEST_PRACTICES'];
                $query = 'url=' . rawurlencode($url)
                    . '&key=' . rawurlencode($key)
                    . '&strategy=' . rawurlencode($strategy);
                foreach ($categories as $c) {
                    $query .= '&category=' . rawurlencode($c);
                }

                $resp = Http::timeout(60)->get($endpoint . '?' . $query);
                if (!$resp->successful()) {
                    $out[$strategy] = ['error' => 'HTTP ' . $resp->status()];
                    continue;
                }
                $raw = $resp->json();
                $out[$strategy] = $this->normalizePsiResponse(is_array($raw) ? $raw : []);
            } catch (\Throwable $e) {
                $out[$strategy] = ['error' => $e->getMessage()];
            }
        }

        return $out;
    }

    /**
     * Keep only the subset we care about to avoid huge payloads.
     *
     * @param array<string,mixed> $raw
     * @return array<string,mixed>
     */
    protected function normalizePsiResponse(array $raw): array
    {
        $lhr = (array) ($raw['lighthouseResult'] ?? []);
        $cats = (array) ($lhr['categories'] ?? []);
        $audits = (array) ($lhr['audits'] ?? []);

        $metric = function (string $id) use ($audits) {
            $row = (array) ($audits[$id] ?? []);
            return [
                'score' => $row['score'] ?? null,
                'displayValue' => $row['displayValue'] ?? null,
                'numericValue' => $row['numericValue'] ?? null,
                'unit' => $row['numericUnit'] ?? null,
            ];
        };

        return [
            'lighthouse_categories' => [
                'performance_score' => isset($cats['performance']['score']) ? (int) round(((float) $cats['performance']['score']) * 100) : null,
                'accessibility_score' => isset($cats['accessibility']['score']) ? (int) round(((float) $cats['accessibility']['score']) * 100) : null,
                'seo_score' => isset($cats['seo']['score']) ? (int) round(((float) $cats['seo']['score']) * 100) : null,
                'best_practices_score' => isset($cats['best-practices']['score']) ? (int) round(((float) $cats['best-practices']['score']) * 100) : null,
            ],
            'audits' => [
                'first_contentful_paint' => $metric('first-contentful-paint'),
                'largest_contentful_paint' => $metric('largest-contentful-paint'),
                'cumulative_layout_shift' => $metric('cumulative-layout-shift'),
                'interactive' => $metric('interactive'),
                'total_blocking_time' => $metric('total-blocking-time'),
                'speed_index' => $metric('speed-index'),
                'server_response_time' => $metric('server-response-time'),
            ],
            'analysis' => [
                'final_url' => $lhr['finalUrl'] ?? null,
                'fetch_time' => $lhr['fetchTime'] ?? null,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $psi
     * @return array{lcp_ms:?int, fcp_ms:?int, cls:?float, fid_ms:?int, tbt_ms:?int}
     */
    protected function extractCwvFromPsi(array $psi): array
    {
        $mobile = (array) ($psi['mobile'] ?? []);
        $audits = (array) ($mobile['audits'] ?? []);

        $fcp = $this->asInt(data_get($audits, 'first_contentful_paint.numericValue'));
        $lcp = $this->asInt(data_get($audits, 'largest_contentful_paint.numericValue'));
        $cls = $this->asFloat(data_get($audits, 'cumulative_layout_shift.numericValue'));

        // FID isn't available in Lighthouse; use TBT as proxy.
        $tbt = $this->asInt(data_get($audits, 'total_blocking_time.numericValue'));

        return [
            'lcp_ms' => $lcp,
            'fcp_ms' => $fcp,
            'cls' => $cls,
            'fid_ms' => null,
            'tbt_ms' => $tbt,
        ];
    }

    /**
     * Heuristic: flag images with no alt AND likely large by file extension/URL (no byte size known).
     *
     * @param array<string,mixed> $crawl
     * @return array{count:int, examples:array<int,array{page:string,src:?string}>}
     */
    protected function findLargeImagesHeuristic(array $crawl): array
    {
        $pages = (array) ($crawl['pages'] ?? []);
        $examples = [];

        foreach ($pages as $p) {
            $pageUrl = (string) ($p['url'] ?? '');
            $imgs = (array) data_get($p, 'images.items', []);
            foreach ($imgs as $img) {
                $src = (string) ($img['src_resolved'] ?? $img['src'] ?? '');
                if ($src === '') continue;
                // crude: common unoptimized patterns
                if (preg_match('/\.(png|jpg|jpeg)(\?|$)/i', $src) && !preg_match('/(w=|width=|resize=|format=webp|\.webp|\.avif)/i', $src)) {
                    $examples[] = ['page' => $pageUrl, 'src' => $src];
                    if (count($examples) >= 25) break 2;
                }
            }
        }

        return [
            'count' => count($examples),
            'examples' => array_slice($examples, 0, 20),
        ];
    }

    protected function percentile(array $values, int $p): ?int
    {
        $values = array_values(array_filter($values, fn ($v) => is_numeric($v)));
        if (empty($values)) return null;
        sort($values);
        $idx = (int) round((($p / 100) * (count($values) - 1)));
        $idx = max(0, min(count($values) - 1, $idx));
        return (int) $values[$idx];
    }

    protected function normalizeTextForHash(string $s): string
    {
        $s = strip_tags($s);
        $s = strtolower($s);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return trim($s);
    }

    protected function asInt(mixed $v): ?int
    {
        if ($v === null) return null;
        if (is_int($v)) return $v;
        if (is_numeric($v)) return (int) round((float) $v);
        return null;
    }

    protected function asFloat(mixed $v): ?float
    {
        if ($v === null) return null;
        if (is_float($v)) return $v;
        if (is_numeric($v)) return (float) $v;
        return null;
    }
}

