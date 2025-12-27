<?php

namespace App\Http\Livewire\Partners;

use App\Models\Partner;
use App\Models\Referral;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ReferralDashboard extends Component
{
    public ?int $partnerId = null;

    public string $referredName = '';

    public string $referredEmail = '';

    public function create(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        Validator::make([
            'partnerId' => $this->partnerId,
            'referredEmail' => $this->referredEmail,
        ], [
            'partnerId' => ['nullable', 'integer', 'exists:partners,id'],
            'referredEmail' => ['nullable', 'email', 'max:255'],
        ])->validate();

        Referral::create([
            'partner_id' => $this->partnerId ?: null,
            'client_id' => null,
            'referred_name' => trim($this->referredName) ?: null,
            'referred_email' => trim($this->referredEmail) ?: null,
            'status' => 'pending',
        ]);

        $this->reset(['partnerId', 'referredName', 'referredEmail']);
        session()->flash('success', 'Referral logged.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $partners = Partner::query()->orderBy('name')->get(['id', 'name']);
        $referrals = Referral::query()->with('partner')->orderByDesc('id')->limit(200)->get();

        return view('livewire.partners.referral-dashboard', compact('partners', 'referrals'));
    }
}
