<?php

namespace App\Http\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $role = 'all'; // all|super_admin|admin|staff|client

    public string $status = 'all'; // all|active|inactive|suspended

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    protected function baseQuery(): Builder
    {
        return User::query()
            ->with(['roles', 'client'])
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', $s)
                        ->orWhere('email', 'like', $s);
                });
            })
            ->when($this->role !== 'all', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->role)))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->orderBy('name');
    }

    public function toggleActive(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $new = ! ((bool) $user->is_active);
        $user->update([
            'is_active' => $new,
            'status' => $new ? 'active' : 'inactive',
        ]);
        session()->flash('success', 'User status updated.');
    }

    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => $this->baseQuery()->paginate(25),
            'roles' => ['super_admin', 'admin', 'staff', 'client'],
            'statuses' => ['active', 'inactive', 'suspended'],
        ])->layout('layouts.admin', ['title' => 'Users']);
    }
}
