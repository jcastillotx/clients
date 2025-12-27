<?php

namespace App\Http\Livewire\Partners;

use App\Models\Partner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class PartnerManager extends Component
{
    public string $name = '';
    public string $email = '';
    public string $code = '';
    public string $commissionRate = '10.00';
    public bool $isActive = true;

    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        $this->code = strtoupper(Str::random(8));
    }

    public function create(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        Validator::make([
            'name' => $this->name,
            'code' => $this->code,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'unique:partners,code'],
        ])->validate();

        Partner::create([
            'name' => trim($this->name),
            'email' => trim($this->email) ?: null,
            'code' => strtoupper(trim($this->code)),
            'commission_rate' => (float) $this->commissionRate,
            'is_active' => (bool) $this->isActive,
        ]);

        $this->reset(['name', 'email']);
        $this->code = strtoupper(Str::random(8));
        $this->commissionRate = '10.00';
        $this->isActive = true;
        session()->flash('success', 'Partner created.');
    }

    public function toggle(int $id): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        $p = Partner::query()->findOrFail($id);
        $p->update(['is_active' => !$p->is_active]);
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $partners = Partner::query()->orderByDesc('id')->limit(200)->get();
        return view('livewire.partners.partner-manager', compact('partners'));
    }
}

