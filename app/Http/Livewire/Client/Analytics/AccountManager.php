<?php

namespace App\Http\Livewire\Client\Analytics;

use App\Models\AnalyticsAccount;
use Livewire\Component;

class AccountManager extends Component
{
    public $accounts = [];

    public $refreshingAccountId = null;

    protected $listeners = ['accountConnected' => '$refresh'];

    public function mount()
    {
        $this->loadAccounts();
    }

    public function loadAccounts()
    {
        $this->accounts = AnalyticsAccount::where('client_id', auth()->user()->client_id)
            ->orderBy('platform')
            ->get()
            ->toArray();
    }

    public function refreshAccount($accountId)
    {
        $account = AnalyticsAccount::find($accountId);

        if (! $account || $account->client_id !== auth()->user()->client_id) {
            session()->flash('error', 'Account not found.');

            return;
        }

        $this->refreshingAccountId = $accountId;

        try {
            // TODO: Implement analytics token refresh logic
            // This will depend on the specific analytics platform
            session()->flash('info', 'Token refresh is being implemented for analytics platforms.');
            $this->loadAccounts();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to refresh token: '.$e->getMessage());
        } finally {
            $this->refreshingAccountId = null;
        }
    }

    public function render()
    {
        $googleAnalyticsConfigured = !empty(config('services.google.client_id'));

        return view('livewire.client.analytics.account-manager', [
            'availablePlatforms' => [
                'google_analytics_4' => [
                    'name' => 'Google Analytics 4',
                    'icon' => 'fab fa-google',
                    'color' => '#F9AB00',
                    'description' => 'Connect your Google Analytics 4 property to track website metrics',
                    'route' => 'oauth.analytics.google.redirect',
                    'disabled' => !$googleAnalyticsConfigured,
                    'requiresConfig' => !$googleAnalyticsConfigured,
                ],
                'adobe_analytics' => [
                    'name' => 'Adobe Analytics',
                    'icon' => 'fas fa-chart-line',
                    'color' => '#FF0000',
                    'description' => 'Connect your Adobe Analytics account for advanced tracking',
                    'disabled' => true,
                    'comingSoon' => true,
                ],
                'matomo' => [
                    'name' => 'Matomo',
                    'icon' => 'fas fa-chart-area',
                    'color' => '#3152A0',
                    'description' => 'Connect your self-hosted or cloud Matomo instance',
                    'disabled' => true,
                    'comingSoon' => true,
                ],
            ],
        ]);
    }
}
