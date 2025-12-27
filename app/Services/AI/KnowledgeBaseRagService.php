<?php

namespace App\Services\AI;

use App\Models\Document;
use App\Models\KnowledgeBaseDocument;

class KnowledgeBaseRagService
{
    public function __construct(
        protected DocumentEmbeddingService $embeddings,
        protected DocumentSemanticSearchService $search,
        protected DocumentTextExtractor $extractor
    ) {}

    /**
     * Retrieve top matching knowledge base documents for a query.
     *
     * @return array<int, array{document:Document, score:float, snippet:string}>
     */
    public function retrieve(string $query, int $limit = 4): array
    {
        $kbIds = KnowledgeBaseDocument::query()->pluck('document_id')->all();
        if (empty($kbIds)) {
            return [];
        }

        $vec = $this->embeddings->embedText($query, ['provider' => 'openai']);
        if (! $vec) {
            return [];
        }

        $ranked = $this->search->findSimilarByEmbedding($vec, limit: max(1, $limit), candidateLimit: 1200);
        $ids = array_values(array_unique(array_map(fn ($r) => (int) $r['document_id'], $ranked)));
        $docs = Document::query()->whereIn('id', $ids)->get()->keyBy('id');

        $out = [];
        foreach ($ranked as $r) {
            $id = (int) $r['document_id'];
            if (! in_array($id, $kbIds, true)) {
                continue;
            }
            /** @var Document|null $doc */
            $doc = $docs->get($id);
            if (! $doc) {
                continue;
            }

            $snippet = $this->snippetFor($doc);
            $out[] = [
                'document' => $doc,
                'score' => (float) $r['score'],
                'snippet' => $snippet,
            ];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    protected function snippetFor(Document $document, int $maxChars = 1500): string
    {
        $ex = $this->extractor->extractFromStorage('documents', (string) $document->file_path, $document->mime_type, $document->original_filename);
        $text = trim((string) ($ex['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
        }

        return $text;
    }
}
