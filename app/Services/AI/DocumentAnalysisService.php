<?php

namespace App\Services\AI;

use App\Models\Document;
use App\Models\RequestEstimate;
use App\Services\AI\Prompts\DocumentAnalysisPrompts;
use Illuminate\Support\Facades\Log;

class DocumentAnalysisService
{
    public function __construct(
        protected AIProviderManager $providers,
        protected DocumentTextExtractor $extractor
    ) {
    }

    /**
     * Detect document type from extracted content + metadata.
     *
     * @return 'contract'|'invoice'|'technical'|'unknown'
     */
    public function detectDocumentType(string $text, ?string $filename = null, ?string $mimeType = null): string
    {
        $hay = strtolower($text);
        $fn = strtolower((string) $filename);

        if (str_contains($fn, 'invoice') || str_contains($hay, 'invoice') || (str_contains($hay, 'subtotal') && str_contains($hay, 'total'))) {
            return 'invoice';
        }

        if (str_contains($fn, 'contract') || str_contains($hay, 'agreement') || str_contains($hay, 'party') && str_contains($hay, 'term')) {
            return 'contract';
        }

        if (
            str_contains($hay, 'requirements') ||
            str_contains($hay, 'api') ||
            str_contains($hay, 'endpoint') ||
            str_contains($hay, 'framework') ||
            str_contains($hay, 'dependency')
        ) {
            return 'technical';
        }

        return 'unknown';
    }

    /**
     * Route analysis based on detected type.
     *
     * @return array<string,mixed>
     */
    public function analyzeDocument(Document $document, array $options = []): array
    {
        $document->loadMissing(['client', 'request']);

        $ex = $this->extractor->extractFromStorage('documents', (string) $document->file_path, $document->mime_type, $document->original_filename);
        $text = trim((string) ($ex['text'] ?? ''));
        $type = (string) ($options['force_type'] ?? $this->detectDocumentType($text, $document->original_filename, $document->mime_type));

        return match ($type) {
            'contract' => $this->analyzeContract($document, $text, $ex, $options),
            'invoice' => $this->analyzeInvoice($document, $text, $ex, $options),
            'technical' => $this->analyzeTechnicalDocument($document, $text, $ex, $options),
            default => $this->summarizeGeneric($document, $text, $ex, $options),
        };
    }

    /**
     * Contract review assistant (use Claude).
     *
     * @return array<string,mixed>
     */
    public function analyzeContract(Document $document, string $text, array $extraction, array $options = []): array
    {
        $system = DocumentAnalysisPrompts::contractSystem();

        $standard = (string) ($options['standard_template_text'] ?? DocumentAnalysisPrompts::defaultContractChecklist());
        $user = DocumentAnalysisPrompts::contractUser($document, $text, $standard, $extraction);

        return $this->chatJson('claude', 'analyze_contract', $system, $user, $document, $options);
    }

    /**
     * Invoice analysis (extract line items, verify calculations, compare vs scope/estimate).
     *
     * @return array<string,mixed>
     */
    public function analyzeInvoice(Document $document, string $text, array $extraction, array $options = []): array
    {
        $system = DocumentAnalysisPrompts::invoiceSystem();

        $estimate = null;
        if ($document->request_id) {
            $estimate = RequestEstimate::query()
                ->where('request_id', $document->request_id)
                ->orderByDesc('id')
                ->value('estimate_data');
        }

        $user = DocumentAnalysisPrompts::invoiceUser($document, $text, is_array($estimate) ? $estimate : null, $extraction);
        return $this->chatJson('openai', 'analyze_invoice', $system, $user, $document, $options);
    }

    /**
     * Technical document analysis.
     *
     * @return array<string,mixed>
     */
    public function analyzeTechnicalDocument(Document $document, string $text, array $extraction, array $options = []): array
    {
        $system = DocumentAnalysisPrompts::technicalSystem();
        $user = DocumentAnalysisPrompts::technicalUser($document, $text, $extraction);
        return $this->chatJson('openrouter', 'analyze_technical_document', $system, $user, $document, $options);
    }

    /**
     * Fallback summarizer with action items + highlights (supports language).
     *
     * @return array<string,mixed>
     */
    public function summarizeGeneric(Document $document, string $text, array $extraction, array $options = []): array
    {
        $lang = (string) ($options['language'] ?? 'en');
        $system = DocumentAnalysisPrompts::summarySystem($lang);
        $user = DocumentAnalysisPrompts::summaryUser($document, $text, $extraction);
        return $this->chatJson('openai', 'summarize_document', $system, $user, $document, $options);
    }

    /**
     * @return array<string,mixed>
     */
    protected function chatJson(string $preferredProvider, string $taskType, string $system, string $user, Document $document, array $options): array
    {
        try {
            $res = $this->providers->withFallback($preferredProvider, function ($provider) use ($system, $user, $taskType, $document, $options) {
                return $provider->chat([
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ], [
                    'task_type' => $taskType,
                    'client_id' => $document->client_id,
                    'user_id' => $document->uploaded_by,
                    'timeout' => (int) ($options['timeout'] ?? 120),
                    'task_id' => $options['task_id'] ?? null,
                    'model' => $options['model'] ?? null,
                ]);
            }, $taskType);

            $data = $this->parseJsonFromText((string) ($res['text'] ?? ''));
            $data['_meta'] = array_merge((array) ($data['_meta'] ?? []), [
                'provider' => $res['provider'] ?? null,
                'model' => $res['model'] ?? null,
                'tokens' => $res['tokens'] ?? null,
                'estimated_cost' => $res['estimated_cost'] ?? null,
            ]);

            return $data;
        } catch (\Throwable $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'api key') || str_contains($msg, 'not configured')) {
                return [
                    'error' => 'AI provider not configured.',
                    '_meta' => ['provider' => $preferredProvider],
                ];
            }
            Log::warning('Document analysis failed: ' . $e->getMessage());
            return [
                'error' => $e->getMessage(),
                '_meta' => ['provider' => $preferredProvider],
            ];
        }
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseJsonFromText(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];

        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) return $decoded;
        }

        return [];
    }
}

