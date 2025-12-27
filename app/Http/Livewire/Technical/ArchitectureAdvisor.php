<?php

namespace App\Http\Livewire\Technical;

use App\Services\AI\CodeReviewService;
use Livewire\Component;

class ArchitectureAdvisor extends Component
{
    public string $provider = 'claude';

    public string $model = '';

    public string $designDoc = '';

    public string $requirementsJson = '';

    public string $errorLogs = '';

    /** @var array<string,mixed>|null */
    public ?array $architecture = null;

    /** @var array<string,mixed>|null */
    public ?array $stack = null;

    /** @var array<string,mixed>|null */
    public ?array $debug = null;

    public function mount(): void
    {
        $this->designDoc = "System overview:\n- Users\n- Data flows\n- Storage\n- Queue/Workers\n- Auth\n\nOpen questions:\n- ...";
        $this->requirementsJson = json_encode([
            'domain' => 'SaaS web app',
            'traffic' => 'moderate',
            'budget' => 'mid',
            'team_expertise' => ['PHP/Laravel', 'MySQL', 'AWS'],
            'constraints' => ['SOC2'],
        ], JSON_PRETTY_PRINT);
        $this->errorLogs = '';
    }

    public function reviewArchitecture(CodeReviewService $svc): void
    {
        $this->architecture = $svc->reviewArchitecture($this->designDoc, [
            'provider' => $this->provider,
            'model' => trim($this->model) !== '' ? $this->model : null,
            'task_type' => 'architecture_review',
            'timeout' => 240,
            'user_id' => auth()->id(),
            'user_query' => 'Architecture review',
        ]);
    }

    public function recommendStack(CodeReviewService $svc): void
    {
        $req = json_decode($this->requirementsJson, true);
        if (! is_array($req)) {
            session()->flash('error', 'Requirements JSON is invalid.');

            return;
        }

        $this->stack = $svc->recommendTechStack($req, [
            'provider' => $this->provider,
            'model' => trim($this->model) !== '' ? $this->model : null,
            'task_type' => 'tech_stack_recommendation',
            'timeout' => 240,
            'user_id' => auth()->id(),
            'user_query' => 'Tech stack recommendation',
        ]);
    }

    public function debug(CodeReviewService $svc): void
    {
        if (trim($this->errorLogs) === '') {
            session()->flash('error', 'Paste logs first.');

            return;
        }

        $this->debug = $svc->debugLogs($this->errorLogs, [
            'provider' => $this->provider,
            'model' => trim($this->model) !== '' ? $this->model : null,
            'task_type' => 'debug_assistant',
            'timeout' => 180,
            'user_id' => auth()->id(),
            'user_query' => 'Debug logs',
        ]);
    }

    public function render()
    {
        return view('livewire.technical.architecture-advisor');
    }
}
