<?php

namespace App\Http\Livewire\Onboarding;

use App\Models\Questionnaire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class QuestionnaireBuilder extends Component
{
    public ?int $questionnaireId = null;
    public string $title = '';
    public string $questionnaireType = 'custom';
    public array $questions = [];

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
    }

    public function addQuestion(): void
    {
        $this->questions[] = [
            'key' => 'q' . (count($this->questions) + 1),
            'type' => 'text',
            'label' => 'Question',
            'required' => false,
            'options' => [],
        ];
    }

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make([
            'title' => $this->title,
            'questionnaireType' => $this->questionnaireType,
            'questions' => $this->questions,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'questionnaireType' => ['required', 'string', 'max:60'],
            'questions' => ['array', 'min:1'],
        ])->validate();

        $q = $this->questionnaireId ? Questionnaire::query()->findOrFail($this->questionnaireId) : new Questionnaire();
        $q->fill([
            'client_id' => $q->client_id, // set by admin later, or use as global template; keep as-is
            'questionnaire_type' => $this->questionnaireType,
            'title' => $this->title,
            'questions' => $this->questions,
            'status' => 'draft',
        ]);
        $q->save();

        $this->questionnaireId = $q->id;
        session()->flash('success', 'Questionnaire saved.');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $existing = null;
        if ($this->questionnaireId) {
            $existing = Questionnaire::query()->find($this->questionnaireId);
        }

        return view('livewire.onboarding.questionnaire-builder', [
            'existing' => $existing,
        ])->layout('layouts.admin', ['title' => 'Questionnaire Builder']);
    }
}

