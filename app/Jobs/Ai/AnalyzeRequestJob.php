<?php

namespace App\Jobs\Ai;

use App\Models\AiTask;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Notifications\RequestAiAnalysisCompletedNotification;
use App\Services\AI\RequestEnhancementService;
use App\Services\AI\RequestTriageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class AnalyzeRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 180, 420];

    /**
     * @param array{provider?:string, model?:string} $overrides
     */
    public function __construct(
        public int $requestId,
        public array $overrides = []
    ) {
        $this->onQueue('ai');
    }

    public function handle(RequestTriageService $triage, RequestEnhancementService $enhancement): void
    {
        /** @var ServiceRequest|null $request */
        $request = ServiceRequest::query()->with(['client', 'creator', 'attachments'])->find($this->requestId);
        if (!$request) {
            return;
        }

        $task = AiTask::create([
            'task_type' => 'analyze_request',
            'input_data' => [
                'request_id' => $request->id,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'priority' => $request->priority,
                'client_id' => $request->client_id,
                'created_by' => $request->created_by,
                'overrides' => $this->overrides,
            ],
            'status' => 'processing',
            'executed_by' => null,
        ]);

        try {
            $triageResult = $triage->analyzeNewRequest($request, [
                'task_id' => $task->id,
                'provider' => $this->overrides['provider'] ?? null,
                'model' => $this->overrides['model'] ?? null,
                'complexity' => 'high',
                'timeout' => 90,
            ]);

            $clarify = $enhancement->clarifyRequest($request, [
                'task_id' => $task->id,
                'provider' => $this->overrides['provider'] ?? null,
                'model' => $this->overrides['model'] ?? null,
                'complexity' => 'medium',
                'timeout' => 75,
            ]);

            $scope = $enhancement->generateScope($request, [
                'task_id' => $task->id,
                'provider' => $this->overrides['provider'] ?? null,
                'model' => $this->overrides['model'] ?? null,
                'complexity' => 'high',
                'timeout' => 120,
            ]);

            $output = [
                'triage' => $triageResult,
                'clarification' => $clarify,
                'scope' => $scope,
            ];

            $task->update([
                'output_data' => $output,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Apply conservative auto-population + internal note.
            $triage->applyTriage($request->fresh(), $triageResult);

            // Notify admins/staff that analysis is available (best-effort).
            $recipients = User::query()->role(['super_admin', 'admin', 'staff'])->get();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new RequestAiAnalysisCompletedNotification($request->fresh()));
            }
        } catch (\Throwable $e) {
            Log::warning('AI request analysis failed: ' . $e->getMessage(), [
                'request_id' => $request->id,
            ]);

            $task->update([
                'status' => 'failed',
                'output_data' => [
                    'error' => $e->getMessage(),
                ],
                'completed_at' => now(),
            ]);

            // Avoid retry storms when AI isn't configured (best-effort workflow).
            $msg = strtolower($e->getMessage());
            if (
                str_contains($msg, 'api key') ||
                str_contains($msg, 'not configured') ||
                str_contains($msg, 'missing') && str_contains($msg, 'key')
            ) {
                return;
            }

            throw $e; // allow retries for transient provider failures
        }
    }
}

