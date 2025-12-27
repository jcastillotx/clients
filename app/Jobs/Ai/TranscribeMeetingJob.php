<?php

namespace App\Jobs\Ai;

use App\Models\AiTask;
use App\Services\AI\MeetingAssistantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranscribeMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int,int> */
    public array $backoff = [60, 180, 420];

    /**
     * @param  array{participants?:array<int,string>, purpose?:string, language?:string}  $options
     */
    public function __construct(public string $audioPath, public array $options = [])
    {
        $this->onQueue('ai');
    }

    public function handle(MeetingAssistantService $svc): void
    {
        $task = AiTask::create([
            'task_type' => 'meeting_notes',
            'input_data' => [
                'audio_path' => $this->audioPath,
                'options' => $this->options,
            ],
            'status' => 'processing',
            'executed_by' => null,
        ]);

        try {
            $result = $svc->transcribeAndSummarize($this->audioPath, array_merge($this->options, [
                'task_id' => $task->id,
                'disk' => 'attachments',
            ]));

            $task->update([
                'output_data' => $result,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('TranscribeMeetingJob failed: '.$e->getMessage());
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
