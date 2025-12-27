<?php

namespace App\Jobs\Ai;

use App\Models\AiTask;
use App\Models\Request as ServiceRequest;
use App\Services\AI\RequestTriageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TriageRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $requestId)
    {
        $this->onQueue('ai');
    }

    public function handle(RequestTriageService $triage): void
    {
        /** @var ServiceRequest|null $request */
        $request = ServiceRequest::query()->with(['client', 'creator', 'attachments'])->find($this->requestId);
        if (! $request) {
            return;
        }

        // Best-effort task record for auditing.
        $task = AiTask::create([
            'task_type' => 'triage_request',
            'input_data' => [
                'request_id' => $request->id,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'priority' => $request->priority,
                'client_id' => $request->client_id,
                'created_by' => $request->created_by,
            ],
            'status' => 'processing',
            'executed_by' => null,
        ]);

        try {
            $analysis = $triage->analyzeNewRequest($request, [
                'task_id' => $task->id,
                'complexity' => 'high',
                // preferred provider can be overridden by config via AIProviderManager routing
            ]);

            $task->update([
                'output_data' => $analysis,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $triage->applyTriage($request->fresh(), $analysis);
        } catch (\Throwable $e) {
            Log::warning('AI request triage failed: '.$e->getMessage(), [
                'request_id' => $request->id,
            ]);

            $task->update([
                'status' => 'failed',
                'output_data' => [
                    'error' => $e->getMessage(),
                ],
                'completed_at' => now(),
            ]);
        }
    }
}
