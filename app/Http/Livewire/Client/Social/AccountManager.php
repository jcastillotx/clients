<?php

namespace App\Http\Livewire\Client\Social;

use App\Models\SocialAccount;
use App\Services\Social\FacebookOAuthService;
use App\Services\Social\LinkedInOAuthService;
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
        $this->accounts = SocialAccount::where('client_id', auth()->user()->client_id)
            ->orderBy('platform')
            ->get()
            ->toArray();
    }

    public function refreshAccount($accountId)
    {
        $account = SocialAccount::find($accountId);

        if (! $account || $account->client_id !== auth()->user()->client_id) {
            session()->flash('error', 'Account not found.');

            return;
        }

        $this->refreshingAccountId = $accountId;

        try {
            $service = match ($account->platform) {
                'facebook' => new FacebookOAuthService,
                'linkedin' => new LinkedInOAuthService,
                default => null,
            };

            if (! $service) {
                throw new \Exception('OAuth service not available for this platform');
            }

            if ($service->refreshToken($account)) {
                session()->flash('success', ucfirst($account->platform).' token refreshed successfully!');
                $this->loadAccounts();
            } else {
                throw new \Exception('Token refresh failed. Please reconnect your account.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to refresh token: '.$e->getMessage());
        } finally {
            $this->refreshingAccountId = null;
        }
    }

    public function render()
    {
        return view('livewire.client.social.account-manager', [
            'availablePlatforms' => [
                'facebook' => [
                    'name' => 'Facebook',
                    'icon' => 'fab fa-facebook',
                    'color' => '#1877F2',
                    'description' => 'Connect your Facebook Page to publish posts',
                ],
                'linkedin' => [
                    'name' => 'LinkedIn',
                    'icon' => 'fab fa-linkedin',
                    'color' => '#0A66C2',
                    'description' => 'Connect your LinkedIn profile or company page',
                ],
                'instagram' => [
                    'name' => 'Instagram',
                    'icon' => 'fab fa-instagram',
                    'color' => '#E4405F',
                    'description' => 'Connect your Instagram Business account (via Facebook)',
                    'disabled' => true,
                    'comingSoon' => true,
                ],
                'x' => [
                    'name' => 'X (Twitter)',
                    'icon' => 'fab fa-x-twitter',
                    'color' => '#000000',
                    'description' => 'Connect your X (formerly Twitter) account',
                    'disabled' => true,
                    'comingSoon' => true,
                ],
            ],
        ]);
    }
}
