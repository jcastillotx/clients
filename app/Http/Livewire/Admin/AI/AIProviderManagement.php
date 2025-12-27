<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\AiProvider;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AIProviderManagement extends Component
{
    public function toggleStatus(int $id): void
    {
        $this->authorizeSuperAdmin();

        $p = AiProvider::query()->findOrFail($id);
        $p->update(['status' => $p->status === 'active' ? 'inactive' : 'active']);
        session()->flash('success', 'Provider status updated.');
    }

    public function setDefault(int $id): void
    {
        $this->authorizeSuperAdmin();

        $p = AiProvider::query()->findOrFail($id);
        AiProvider::query()->where('name', $p->name)->update(['is_default' => false]);
        $p->update(['is_default' => true, 'status' => 'active']);
        session()->flash('success', 'Default provider updated.');
    }

    public function updatePriority(int $id, int $priority): void
    {
        $this->authorizeSuperAdmin();

        $p = AiProvider::query()->findOrFail($id);
        $p->update(['priority_order' => max(0, $priority)]);
        session()->flash('success', 'Priority updated.');
    }

    public function delete(int $id): void
    {
        $this->authorizeSuperAdmin();
        AiProvider::query()->whereKey($id)->delete();
        session()->flash('success', 'Provider configuration removed.');
    }

    protected function authorizeSuperAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->hasRole('super_admin')) {
            abort(403, 'Only super admins can manage AI providers.');
        }
    }

    public function maskKey(?string $key): string
    {
        $k = trim((string) $key);
        if ($k === '') {
            return '—';
        }
        $last = substr($k, -4);

        return '••••••••'.($last ? " {$last}" : '');
    }

    public function render()
    {
        $providers = AiProvider::query()
            ->orderBy('name')
            ->orderByDesc('is_default')
            ->orderBy('priority_order')
            ->get();

        return view('livewire.admin.ai.provider-management', [
            'providers' => $providers,
            'canEdit' => Auth::user()?->hasRole('super_admin') ?? false,
        ])->layout('layouts.admin', ['title' => 'AI Providers']);
    }
}
