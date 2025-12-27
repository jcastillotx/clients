<?php

namespace App\Services\Marketing\Scoring;

class WebsiteAuditScorer
{
    /**
     * Compute category + overall scores from issues and key metrics.
     *
     * @param  array<int, array{severity:string, category:string, issue_type:string, affected_url?:?string, meta?:array|null}>  $issues
     * @param  array<string, mixed>  $metrics
     * @return array{overall:int, seo:int, performance:int, accessibility:int, security:int, mobile:int, weights:array<string,float>}
     */
    public function score(array $issues, array $metrics = []): array
    {
        $weights = [
            'seo' => 0.30,
            'performance' => 0.30,
            'accessibility' => 0.15,
            'security' => 0.15,
            'mobile' => 0.10,
        ];

        $base = [
            'seo' => 100,
            'performance' => 100,
            'accessibility' => 100,
            'security' => 100,
            'mobile' => 100,
        ];

        $sevPenalty = [
            'critical' => 18,
            'error' => 10,
            'warning' => 5,
            'info' => 2,
        ];

        foreach ($issues as $issue) {
            $cat = (string) ($issue['category'] ?? 'seo');
            $sev = (string) ($issue['severity'] ?? 'warning');
            $pen = (int) ($sevPenalty[$sev] ?? 5);

            // If issue likely impacts many pages, scale up.
            $meta = is_array($issue['meta'] ?? null) ? (array) $issue['meta'] : [];
            $scope = (string) ($meta['scope'] ?? '');
            if ($scope === 'sitewide') {
                $pen = (int) round($pen * 1.4);
            }
            if ($scope === 'many_pages') {
                $pen = (int) round($pen * 1.2);
            }

            if (! isset($base[$cat])) {
                $cat = 'seo';
            }
            $base[$cat] -= $pen;
        }

        // Performance metric nudges (if we have CWV-ish numbers).
        $lcpMs = $this->asInt($metrics['lcp_ms'] ?? null);
        $cls = $this->asFloat($metrics['cls'] ?? null);
        $ttfbMs = $this->asInt($metrics['ttfb_ms'] ?? null);
        if ($lcpMs !== null) {
            $base['performance'] -= $lcpMs > 4000 ? 20 : ($lcpMs > 2500 ? 10 : 0);
        }
        if ($cls !== null) {
            $base['performance'] -= $cls > 0.25 ? 15 : ($cls > 0.1 ? 8 : 0);
        }
        if ($ttfbMs !== null) {
            $base['performance'] -= $ttfbMs > 800 ? 10 : ($ttfbMs > 500 ? 5 : 0);
        }

        // Mobile-friendly signal (viewport present / PSI mobile score).
        $mobileOk = $metrics['mobile_friendly'] ?? null;
        if ($mobileOk === false) {
            $base['mobile'] -= 25;
        }

        $scores = [];
        foreach ($base as $k => $v) {
            $scores[$k] = max(0, min(100, (int) round($v)));
        }

        $overall = 0.0;
        foreach ($weights as $cat => $w) {
            $overall += ($scores[$cat] ?? 0) * $w;
        }

        return [
            'overall' => max(0, min(100, (int) round($overall))),
            'seo' => $scores['seo'],
            'performance' => $scores['performance'],
            'accessibility' => $scores['accessibility'],
            'security' => $scores['security'],
            'mobile' => $scores['mobile'],
            'weights' => $weights,
        ];
    }

    protected function asInt(mixed $v): ?int
    {
        if ($v === null) {
            return null;
        }
        if (is_int($v)) {
            return $v;
        }
        if (is_numeric($v)) {
            return (int) round((float) $v);
        }

        return null;
    }

    protected function asFloat(mixed $v): ?float
    {
        if ($v === null) {
            return null;
        }
        if (is_float($v)) {
            return $v;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        return null;
    }
}
