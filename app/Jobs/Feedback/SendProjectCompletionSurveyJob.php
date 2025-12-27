<?php

namespace App\Jobs\Feedback;

use App\Models\Request as ServiceRequest;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Notifications\SurveyRequestedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SendProjectCompletionSurveyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $requestId) {}

    public function handle(): void
    {
        $req = ServiceRequest::query()->with('client.users')->find($this->requestId);
        if (! $req || ! $req->client) {
            return;
        }

        // Find or create a per-client post-project survey
        $survey = Survey::query()->firstOrCreate(
            [
                'client_id' => $req->client_id,
                'type' => 'project_completion',
                'name' => 'Post-project survey',
            ],
            [
                'description' => 'Quick feedback after project completion.',
                'is_active' => true,
                'anonymous_allowed' => false,
                'created_by' => null,
            ]
        );

        // Ensure minimal questions exist
        if (! $survey->questions()->exists()) {
            SurveyQuestion::create([
                'survey_id' => $survey->id,
                'type' => 'nps',
                'prompt' => 'How likely are you to recommend us to a friend or colleague? (0-10)',
                'is_required' => true,
                'sort_order' => 1,
            ]);
            SurveyQuestion::create([
                'survey_id' => $survey->id,
                'type' => 'rating',
                'prompt' => 'Overall satisfaction (1-5)',
                'is_required' => true,
                'sort_order' => 2,
            ]);
            SurveyQuestion::create([
                'survey_id' => $survey->id,
                'type' => 'text',
                'prompt' => 'Anything we could improve?',
                'is_required' => false,
                'sort_order' => 3,
            ]);
        }

        $token = Str::uuid()->toString();

        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'client_id' => $req->client_id,
            'user_id' => null,
            'anonymous_token' => $token,
            'submitted_at' => null,
            'meta' => [
                'request_id' => $req->id,
                'trigger' => 'request.completed',
            ],
        ]);

        $recipients = $req->client->users;
        if ($recipients && $recipients->isNotEmpty()) {
            Notification::send($recipients, new SurveyRequestedNotification($response));
        }
    }
}
