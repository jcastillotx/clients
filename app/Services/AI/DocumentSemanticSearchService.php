<?php

namespace App\Services\AI;

use App\Models\DocumentEmbedding;

class DocumentSemanticSearchService
{
    /**
     * @param array<int, float|int|string> $a
     * @param array<int, float|int|string> $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) return 0.0;

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
        if ($na <= 0.0 || $nb <= 0.0) return 0.0;
        return $dot / (sqrt($na) * sqrt($nb));
    }

    /**
     * @param array<int, float|int|string> $embedding
     * @return array<int, array{document_id:int, score:float}>
     */
    public function findSimilarByEmbedding(array $embedding, int $limit = 8, int $candidateLimit = 800, ?int $excludeDocumentId = null): array
    {
        $rows = DocumentEmbedding::query()
            ->when($excludeDocumentId, fn ($q) => $q->where('document_id', '!=', $excludeDocumentId))
            ->orderByDesc('id')
            ->limit(max(1, $candidateLimit))
            ->get(['document_id', 'embedding']);

        $ranked = [];
        foreach ($rows as $row) {
            $vec = is_array($row->embedding) ? $row->embedding : [];
            $score = $this->cosineSimilarity($embedding, $vec);
            $ranked[] = ['document_id' => (int) $row->document_id, 'score' => $score];
        }

        usort($ranked, fn ($x, $y) => $y['score'] <=> $x['score']);
        $ranked = array_values(array_filter($ranked, fn ($r) => is_finite($r['score']) && $r['score'] > 0.0));

        return array_slice($ranked, 0, max(1, $limit));
    }
}

