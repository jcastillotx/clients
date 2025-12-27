<?php

namespace App\Http\Livewire\Feedback;

use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class FeedbackCollector extends Component
{
    public string $token;

    public array $answers = []; // question_id => value

    public function mount(string $token): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        $this->token = $token;
    }

    public function submit(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $resp = SurveyResponse::query()
            ->where('anonymous_token', $this->token)
            ->with('survey.questions')
            ->firstOrFail();

        abort_unless((int) $resp->client_id === (int) $user->client_id, 403);
        abort_unless(! $resp->submitted_at, 422);

        // Validate required questions
        foreach ($resp->survey->questions as $q) {
            if (! $q->is_required) {
                continue;
            }
            $val = $this->answers[$q->id] ?? null;
            Validator::make(['v' => $val], ['v' => ['required']])->validate();
        }

        foreach ($resp->survey->questions as $q) {
            SurveyAnswer::updateOrCreate(
                ['response_id' => $resp->id, 'question_id' => $q->id],
                ['value' => (string) ($this->answers[$q->id] ?? '')]
            );
        }

        $resp->update([
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);

        session()->flash('success', 'Thanks for your feedback!');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $resp = SurveyResponse::query()
            ->where('anonymous_token', $this->token)
            ->with('survey.questions')
            ->firstOrFail();

        abort_unless((int) $resp->client_id === (int) $user->client_id, 403);

        return view('livewire.feedback.feedback-collector', [
            'response' => $resp,
            'survey' => $resp->survey,
            'questions' => $resp->survey->questions,
        ]);
    }
}
