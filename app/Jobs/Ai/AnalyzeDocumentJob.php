<?php

namespace App\Jobs\Ai;

use App\Models\AiTask;
use App\Models\Document;
use App\Services\AI\DocumentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int,int> */
    public array $backoff = [60, 180, 420];

    /**
     * @param  array{force_type?:string, language?:string, provider?:string, model?:string}  $options
     */
    public function __construct(public int $documentId, public array $options = [])
    {
        $this->onQueue('ai');
    }

    public function handle(DocumentAnalysisService $svc): void
    {
        /** @var Document|null $doc */
        $doc = Document::query()->with(['client', 'request'])->find($this->documentId);
        if (! $doc) {
            return;
        }

        $task = AiTask::create([
            'task_type' => 'analyze_document',
            'input_data' => [
                'document_id' => $doc->id,
                'client_id' => $doc->client_id,
                'file' => [
                    'name' => $doc->original_filename,
                    'mime' => $doc->mime_type,
                    'size' => $doc->file_size,
                ],
                'options' => $this->options,
            ],
            'status' => 'processing',
            'executed_by' => null,
        ]);

        try {
            $result = $svc->analyzeDocument($doc, array_merge($this->options, [
                'task_id' => $task->id,
            ]));

            $task->update([
                'output_data' => $result,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AnalyzeDocumentJob failed: '.$e->getMessage(), ['document_id' => $doc->id]);
            $task->update([
                'status' => 'failed',
                'output_data' => ['error' => $e->getMessage()],
                'completed_at' => now(),
            ]);

            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'api key') || str_contains($msg, 'not configured')) {
                return;
            }
            throw $e;
        }
    }
}
