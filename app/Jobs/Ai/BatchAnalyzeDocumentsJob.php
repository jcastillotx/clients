<?php

namespace App\Jobs\Ai;

use App\Models\AiTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BatchAnalyzeDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int,int>  $documentIds
     * @param  array{force_type?:string, language?:string}  $options
     */
    public function __construct(public array $documentIds, public array $options = [])
    {
        $this->onQueue('ai');
    }

    public function handle(): void
    {
        // Parent task for tracking.
        $task = AiTask::create([
            'task_type' => 'batch_analyze_documents',
            'input_data' => [
                'document_ids' => $this->documentIds,
                'options' => $this->options,
            ],
            'status' => 'processing',
            'executed_by' => null,
        ]);

        foreach ($this->documentIds as $id) {
            if (! is_int($id) && ! ctype_digit((string) $id)) {
                continue;
            }
            AnalyzeDocumentJob::dispatch((int) $id, $this->options);
        }

        $task->update([
            'status' => 'completed',
            'output_data' => [
                'dispatched' => count($this->documentIds),
            ],
            'completed_at' => now(),
        ]);
    }
}
