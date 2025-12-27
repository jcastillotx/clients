<?php

namespace App\Http\Livewire\Feedback;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class SurveyBuilder extends Component
{
    public ?int $surveyId = null;
    public string $name = '';
    public string $description = '';
    public bool $isActive = true;
    public bool $anonymousAllowed = false;
    public string $type = 'satisfaction';

    public array $questions = []; // [{type,prompt,is_required,sort_order}]

    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
    }

    public function loadSurvey(): void
    {
        if (!$this->surveyId) return;
        $s = Survey::query()->with('questions')->findOrFail($this->surveyId);
        $this->name = (string) $s->name;
        $this->description = (string) ($s->description ?? '');
        $this->isActive = (bool) $s->is_active;
        $this->anonymousAllowed = (bool) $s->anonymous_allowed;
        $this->type = (string) ($s->type ?? 'satisfaction');
        $this->questions = $s->questions->map(fn ($q) => [
            'type' => $q->type,
            'prompt' => $q->prompt,
            'is_required' => (bool) $q->is_required,
            'sort_order' => (int) $q->sort_order,
        ])->values()->all();
    }

    public function addQuestion(): void
    {
        $this->questions[] = [
            'type' => 'text',
            'prompt' => 'Question',
            'is_required' => true,
            'sort_order' => count($this->questions) + 1,
        ];
    }

    public function save(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        Validator::make([
            'name' => $this->name,
            'type' => $this->type,
            'questions' => $this->questions,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:40'],
            'questions' => ['array', 'min:1'],
        ])->validate();

        $survey = $this->surveyId ? Survey::query()->findOrFail($this->surveyId) : new Survey();
        $survey->fill([
            'client_id' => null,
            'name' => trim($this->name),
            'description' => trim($this->description) ?: null,
            'is_active' => (bool) $this->isActive,
            'anonymous_allowed' => (bool) $this->anonymousAllowed,
            'created_by' => $u->id,
            'type' => $this->type,
        ]);
        $survey->save();
        $this->surveyId = $survey->id;

        // Replace questions (MVP)
        SurveyQuestion::query()->where('survey_id', $survey->id)->delete();
        foreach ($this->questions as $q) {
            if (!is_array($q)) continue;
            SurveyQuestion::create([
                'survey_id' => $survey->id,
                'type' => (string) ($q['type'] ?? 'text'),
                'prompt' => (string) ($q['prompt'] ?? ''),
                'is_required' => (bool) ($q['is_required'] ?? true),
                'sort_order' => (int) ($q['sort_order'] ?? 0),
            ]);
        }

        session()->flash('success', 'Survey saved.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $surveys = Survey::query()->orderByDesc('id')->limit(200)->get(['id', 'name', 'type', 'is_active']);
        return view('livewire.feedback.survey-builder', [
            'surveys' => $surveys,
        ]);
    }
}

