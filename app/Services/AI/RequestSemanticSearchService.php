<?php

namespace App\Services\AI;

use App\Models\RequestEmbedding;

class RequestSemanticSearchService
{
    /**
     * @param  array<int, float|int|string>  $a
     * @param  array<int, float|int|string>  $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $va = (float) $a[$i];
            $vb = (float) $b[$i];
            $dot += $va * $vb;
            $na += $va * $va;
            $nb += $vb * $vb;
        }
        if ($na <= 0.0 || $nb <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }

    /**
     * Rank existing request embeddings by similarity.
     *
     * @param  array<int, float|int|string>  $embedding
     * @return array<int, array{request_id:int, score:float}>
     */
    public function findSimilarByEmbedding(array $embedding, int $limit = 5, int $candidateLimit = 500, ?int $excludeRequestId = null): array
    {
        $rows = RequestEmbedding::query()
            ->when($excludeRequestId, fn ($q) => $q->where('request_id', '!=', $excludeRequestId))
            ->orderByDesc('id')
            ->limit(max(1, $candidateLimit))
            ->get(['request_id', 'embedding']);

        $ranked = [];
        foreach ($rows as $row) {
            $vec = is_array($row->embedding) ? $row->embedding : [];
            $score = $this->cosineSimilarity($embedding, $vec);
            $ranked[] = ['request_id' => (int) $row->request_id, 'score' => $score];
        }

        usort($ranked, fn ($x, $y) => $y['score'] <=> $x['score']);

        // Remove near-duplicates / empties
        $ranked = array_values(array_filter($ranked, fn ($r) => is_finite($r['score']) && $r['score'] > 0.0));

        return array_slice($ranked, 0, max(1, $limit));
    }

    /**
     * @param  array<int, array{estimated_hours:float|null, actual_hours:float|null}>  $projects
     * @return array{count:int, median_ratio:float|null}
     */
    public function varianceStats(array $projects): array
    {
        $ratios = [];
        foreach ($projects as $p) {
            $est = $p['estimated_hours'];
            $act = $p['actual_hours'];
            if ($est === null || $act === null) {
                continue;
            }
            if ($est <= 0) {
                continue;
            }
            $ratios[] = $act / $est;
        }

        sort($ratios);
        $count = count($ratios);
        if ($count === 0) {
            return ['count' => 0, 'median_ratio' => null];
        }

        $mid = (int) floor(($count - 1) / 2);
        $median = $ratios[$mid];
        if ($count % 2 === 0) {
            $median = ($ratios[$mid] + $ratios[$mid + 1]) / 2;
        }

        return ['count' => $count, 'median_ratio' => (float) $median];
    }
}
