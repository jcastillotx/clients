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

    /** @var array<int, int> */
    public array $selected = [];

    public bool $selectPage = false;

    public bool $showDeleteConfirmModal = false;

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

    public function updatedSelectPage(bool $value): void
    {
        if (! $value) {
            $this->selected = [];

            return;
        }

        $currentUserId = auth()->id();

        // Select up to 50 users, excluding super admins and the current user
        $this->selected = $this->baseQuery()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->where('id', '!=', $currentUserId)
            ->limit(50)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function confirmBulkDelete(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'No users selected.');

            return;
        }

        $this->showDeleteConfirmModal = true;
    }

    public function cancelBulkDelete(): void
    {
        $this->showDeleteConfirmModal = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            $this->showDeleteConfirmModal = false;

            return;
        }

        $currentUserId = auth()->id();

        // Prevent deleting the currently logged-in user
        $toDelete = array_filter($this->selected, fn ($id) => $id !== $currentUserId);

        if (empty($toDelete)) {
            session()->flash('error', 'Cannot delete your own account.');
            $this->showDeleteConfirmModal = false;
            $this->selectPage = false;
            $this->selected = [];

            return;
        }

        // Exclude super admins from bulk delete for safety
        $superAdminIds = User::query()
            ->whereIn('id', $toDelete)
            ->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->pluck('id')
            ->all();

        $finalToDelete = array_values(array_diff($toDelete, $superAdminIds));

        if (empty($finalToDelete)) {
            session()->flash('error', 'Cannot bulk delete super admin users.');
            $this->showDeleteConfirmModal = false;
            $this->selectPage = false;
            $this->selected = [];

            return;
        }

        $count = User::query()->whereIn('id', $finalToDelete)->delete();

        $skippedCount = count($this->selected) - $count;
        $message = $count . ' user(s) deleted.';
        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' user(s) skipped (super admins or self).';
        }

        session()->flash('success', $message);

        $this->showDeleteConfirmModal = false;
        $this->selectPage = false;
        $this->selected = [];
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
