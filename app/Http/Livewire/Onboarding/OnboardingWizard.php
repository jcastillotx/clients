<?php

namespace App\Http\Livewire\Onboarding;

use App\Models\OnboardingTask;
use App\Models\OnboardingWorkflow;
use App\Models\Questionnaire;
use App\Services\Marketing\OnboardingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class OnboardingWizard extends Component
{
    public ?int $workflowId = null;

    public ?int $questionnaireId = null;

    public array $answers = [];

    public function mount(OnboardingService $svc): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($user->client_id, 403);

        $workflow = $svc->createOnboardingWorkflow($user->client);
        $this->workflowId = $workflow->id;

        $q = Questionnaire::query()
            ->where('client_id', $user->client_id)
            ->where('questionnaire_type', 'brand_discovery')
            ->orderByDesc('id')
            ->first();

        $this->questionnaireId = $q?->id;
        $this->answers = (array) ($q?->answers ?? []);
    }

    public function saveProgress(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($this->questionnaireId, 422);

        $q = Questionnaire::query()
            ->where('client_id', $user->client_id)
            ->findOrFail($this->questionnaireId);

        $q->update([
            'answers' => $this->answers,
            'status' => 'in_progress',
        ]);

        session()->flash('success', 'Progress saved.');
    }

    public function submitQuestionnaire(OnboardingService $svc): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($this->questionnaireId, 422);

        $q = Questionnaire::query()
            ->where('client_id', $user->client_id)
            ->findOrFail($this->questionnaireId);

        // Minimal validation: required questions must be non-empty.
        $requiredKeys = collect((array) ($q->questions ?? []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['required']) && ! empty($row['key']))
            ->map(fn ($row) => (string) $row['key'])
            ->values()
            ->all();

        foreach ($requiredKeys as $key) {
            $val = $this->answers[$key] ?? null;
            $ok = is_array($val) ? ! empty(array_filter($val)) : (trim((string) $val) !== '');
            Validator::make(['v' => $ok], ['v' => ['accepted']])->validate();
        }

        $q->update([
            'answers' => $this->answers,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Mark onboarding task complete (best-effort)
        if ($this->workflowId) {
            $wf = OnboardingWorkflow::query()->where('client_id', $user->client_id)->find($this->workflowId);
            if ($wf) {
                $task = OnboardingTask::query()
                    ->where('onboarding_workflow_id', $wf->id)
                    ->where('task_name', 'Brand questionnaire completed')
                    ->first();
                if ($task) {
                    $task->update(['status' => 'completed', 'completed_at' => now()]);
                }
                $wf->recalcProgress();
            }
        }

        // Auto-populate artifacts
        $svc->applyQuestionnaireAnswersToArtifacts($q, ['created_by' => $user->id]);

        session()->flash('success', 'Questionnaire submitted. Thank you!');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $workflow = $this->workflowId
            ? OnboardingWorkflow::query()->where('client_id', $user->client_id)->with('tasks')->find($this->workflowId)
            : null;

        $questionnaire = $this->questionnaireId
            ? Questionnaire::query()->where('client_id', $user->client_id)->find($this->questionnaireId)
            : null;

        return view('livewire.onboarding.onboarding-wizard', [
            'workflow' => $workflow,
            'questionnaire' => $questionnaire,
        ]);
    }
}
