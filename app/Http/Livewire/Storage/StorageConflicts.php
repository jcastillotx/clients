<?php

namespace App\Http\Livewire\Storage;

use App\Models\ClientStorageSetting;
use App\Models\StorageConnection;
use App\Models\StorageSyncConflict;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class StorageConflicts extends Component
{
    use WithPagination;

    public ?int $clientId = null;
    public string $search = '';
    public string $resolution = 'unresolved';

    public ?int $selectedConflictId = null;
    public ?int $chosen_connection_id = null;
    public string $chosen_path = '';

    protected array $queryString = [
        'search' => ['except' => ''],
        'resolution' => ['except' => 'unresolved'],
    ];

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user?->client_id, 403);
        $this->clientId = $user->client_id;
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingResolution(): void { $this->resetPage(); }

    public function select(int $conflictId): void
    {
        $c = $this->conflictOrFail($conflictId);
        $this->selectedConflictId = $c->id;
        $this->chosen_connection_id = null;
        $this->chosen_path = '';
    }

    public function chooseCandidate(int $conflictId, int $connectionId, string $path): void
    {
        $c = $this->conflictOrFail($conflictId);
        $this->selectedConflictId = $c->id;
        $this->chosen_connection_id = $connectionId;
        $this->chosen_path = $path;
    }

    public function resolveChosen(): void
    {
        Validator::make([
            'selectedConflictId' => $this->selectedConflictId,
            'chosen_connection_id' => $this->chosen_connection_id,
            'chosen_path' => $this->chosen_path,
        ], [
            'selectedConflictId' => ['required', 'integer'],
            'chosen_connection_id' => ['required', 'integer'],
            'chosen_path' => ['required', 'string'],
        ])->validate();

        $c = $this->conflictOrFail((int) $this->selectedConflictId);
        $candidate = collect((array) $c->candidates)->first(function ($row) {
            return (int) ($row['connection_id'] ?? 0) === (int) $this->chosen_connection_id
                && (string) ($row['path'] ?? '') === (string) $this->chosen_path;
        });
        abort_unless($candidate, 422);

        $c->update([
            'chosen' => $candidate,
            'resolution' => 'prefer_newest',
            'notes' => 'Manually chosen by user.',
        ]);

        session()->flash('success', 'Conflict resolved.');
    }

    public function applyRuleToSelected(string $rule): void
    {
        $c = $this->conflictOrFail((int) $this->selectedConflictId);

        $settings = ClientStorageSetting::query()->firstOrCreate(['client_id' => $this->clientId]);
        $primaryId = (int) (StorageConnection::query()->where('client_id', $this->clientId)->where('is_primary', true)->value('id') ?? 0);
        $candidates = collect((array) $c->candidates);

        $chosen = null;
        $resolution = 'unresolved';
        $notes = null;

        if ($rule === 'prefer_primary') {
            $chosen = $candidates->firstWhere('connection_id', $primaryId) ?? $candidates->sortByDesc('modified_at')->first();
            $resolution = 'prefer_primary';
        } elseif ($rule === 'prefer_newest') {
            $chosen = $candidates->sortByDesc('modified_at')->first();
            $resolution = 'prefer_newest';
        } elseif ($rule === 'keep_both') {
            $chosen = null;
            $resolution = 'kept_both';
        } else {
            abort(422);
        }

        // Also persist this rule into settings if user wants (optional but helpful)
        $settings->update(['conflict_rule' => $rule]);

        $c->update([
            'chosen' => $chosen,
            'resolution' => $resolution,
            'notes' => 'Resolved via rule in conflicts UI.',
        ]);

        session()->flash('success', 'Conflict resolved and rule saved.');
    }

    protected function conflictOrFail(int $id): StorageSyncConflict
    {
        $c = StorageSyncConflict::query()->findOrFail($id);
        abort_unless((int) $c->client_id === (int) $this->clientId, 403);
        return $c;
    }

    public function render()
    {
        $query = StorageSyncConflict::query()
            ->where('client_id', $this->clientId)
            ->when($this->resolution, fn ($q) => $q->where('resolution', $this->resolution))
            ->when($this->search, fn ($q) => $q->where('filename', 'like', '%' . $this->search . '%'))
            ->orderByDesc('id');

        $conflicts = $query->paginate(20);

        return view('livewire.storage.conflicts', [
            'conflicts' => $conflicts,
        ]);
    }
}

