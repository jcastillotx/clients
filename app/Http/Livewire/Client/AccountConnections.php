<?php

namespace App\Http\Livewire\Client;

use App\Models\AnalyticsAccount;
use App\Models\SocialAccount;
use Livewire\Component;

class AccountConnections extends Component
{
    public function render()
    {
        $clientId = auth()->user()->client_id;

        $socialAccounts = SocialAccount::where('client_id', $clientId)
            ->orderBy('platform')
            ->get();

        $analyticsAccounts = AnalyticsAccount::where('client_id', $clientId)
            ->orderBy('platform')
            ->get();

        return view('livewire.client.account-connections', [
            'socialAccounts' => $socialAccounts,
            'analyticsAccounts' => $analyticsAccounts,
        ])->layout('layouts.app', ['title' => 'Account Connections']);
    }
}
