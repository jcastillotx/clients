<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\AiTask;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AIAuditLog extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $status = '';
    public string $provider = '';
    public string $taskType = '';
    public string $q = '';

    /** @var array<int, int|string|null> */
    public array $ratings = [];

    /** @var array<int, string|null> */
    public array $ratingNotes = [];

    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingProvider(): void { $this->resetPage(); }
    public function updatingTaskType(): void { $this->resetPage(); }
    public function updatingQ(): void { $this->resetPage(); }

    public function saveRating(int $taskId): void
    {
        $this->authorizeAdmin();

        $rating = $this->ratings[$taskId] ?? null;
        $rating = $rating === '' ? null : $rating;
        $rating = $rating !== null ? (int) $rating : null;
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            session()->flash('error', 'Rating must be 1-5.');
            return;
        }

        $notes = $this->ratingNotes[$taskId] ?? null;

        AiTask::query()->whereKey($taskId)->update([
            'quality_rating' => $rating,
            'quality_notes' => $notes,
            'rated_by' => Auth::id(),
            'rated_at' => now(),
        ]);

        session()->flash('success', 'Rating saved.');
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (!$u || !$u->can('access admin panel')) {
            abort(403);
        }
    }

    public function render()
    {
        $this->authorizeAdmin();

        $query = AiTask::query()->orderByDesc('id');

        if ($this->status !== '') $query->where('status', $this->status);
        if ($this->provider !== '') $query->where('provider_used', $this->provider);
        if ($this->taskType !== '') $query->where('task_type', $this->taskType);
        if (trim($this->q) !== '') {
            $s = '%' . trim($this->q) . '%';
            $query->where(function ($qq) use ($s) {
                $qq->where('task_type', 'like', $s)
                    ->orWhere('provider_used', 'like', $s)
                    ->orWhere('model_used', 'like', $s);
            });
        }

        $tasks = $query->paginate(25);

        // preload current ratings into bound arrays
        foreach ($tasks as $t) {
            $this->ratings[$t->id] = $this->ratings[$t->id] ?? ($t->quality_rating ?? '');
            $this->ratingNotes[$t->id] = $this->ratingNotes[$t->id] ?? ($t->quality_notes ?? '');
        }

        $distinctProviders = AiTask::query()->whereNotNull('provider_used')->distinct()->pluck('provider_used')->all();
        sort($distinctProviders);
        $distinctTaskTypes = AiTask::query()->distinct()->pluck('task_type')->all();
        sort($distinctTaskTypes);

        return view('livewire.admin.ai.audit-log', [
            'tasks' => $tasks,
            'providers' => $distinctProviders,
            'taskTypes' => $distinctTaskTypes,
        ])->layout('layouts.admin', ['title' => 'AI Audit Log']);
    }

    public function previewJson(mixed $data, int $max = 260): string
    {
        if ($data === null) return '';
        $s = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (!is_string($s)) return '';
        $s = trim($s);
        return strlen($s) > $max ? (substr($s, 0, $max) . '…') : $s;
    }
}

