<?php

namespace App\Http\Livewire\Client\Social;

use App\Models\SocialAccount;
use App\Services\Social\BlueskyService;
use App\Services\Social\FacebookOAuthService;
use App\Services\Social\LinkedInOAuthService;
use App\Services\Social\PinterestOAuthService;
use App\Services\Social\TikTokOAuthService;
use App\Services\Social\TwitterOAuthService;
use Livewire\Component;

class AccountManager extends Component
{
    public $accounts = [];

    public $refreshingAccountId = null;

    // Bluesky connection modal
    public $showBlueskyModal = false;
    public $blueskyHandle = '';
    public $blueskyAppPassword = '';

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
                'twitter' => new TwitterOAuthService,
                'pinterest' => new PinterestOAuthService,
                'tiktok' => new TikTokOAuthService,
                'bluesky' => new BlueskyService,
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

    public function openBlueskyModal()
    {
        $this->blueskyHandle = '';
        $this->blueskyAppPassword = '';
        $this->showBlueskyModal = true;
    }

    public function closeBlueskyModal()
    {
        $this->showBlueskyModal = false;
        $this->blueskyHandle = '';
        $this->blueskyAppPassword = '';
    }

    public function connectBluesky()
    {
        $this->validate([
            'blueskyHandle' => 'required|string',
            'blueskyAppPassword' => 'required|string',
        ]);

        try {
            $service = new BlueskyService;
            $service->connect(
                auth()->user()->client_id,
                $this->blueskyHandle,
                $this->blueskyAppPassword
            );

            session()->flash('success', 'Bluesky account connected successfully!');
            $this->closeBlueskyModal();
            $this->loadAccounts();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to connect Bluesky: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $twitterConfigured = !empty(config('services.twitter.client_id'));
        $pinterestConfigured = !empty(config('services.pinterest.client_id'));
        $tiktokConfigured = !empty(config('services.tiktok.client_key'));

        return view('livewire.client.social.account-manager', [
            'availablePlatforms' => [
                'facebook' => [
                    'name' => 'Facebook',
                    'icon' => 'fab fa-facebook',
                    'color' => '#1877F2',
                    'description' => 'Connect your Facebook Page to publish posts',
                    'route' => 'oauth.facebook.redirect',
                ],
                'linkedin' => [
                    'name' => 'LinkedIn',
                    'icon' => 'fab fa-linkedin',
                    'color' => '#0A66C2',
                    'description' => 'Connect your LinkedIn profile or company page',
                    'route' => 'oauth.linkedin.redirect',
                ],
                'twitter' => [
                    'name' => 'X (Twitter)',
                    'icon' => 'fab fa-x-twitter',
                    'color' => '#000000',
                    'description' => 'Connect your X (formerly Twitter) account',
                    'route' => 'oauth.twitter.redirect',
                    'disabled' => !$twitterConfigured,
                    'requiresConfig' => !$twitterConfigured,
                ],
                'bluesky' => [
                    'name' => 'Bluesky',
                    'icon' => 'fas fa-cloud',
                    'color' => '#0085FF',
                    'description' => 'Connect your Bluesky account using an app password',
                    'customConnect' => true, // Uses modal instead of OAuth
                ],
                'pinterest' => [
                    'name' => 'Pinterest',
                    'icon' => 'fab fa-pinterest',
                    'color' => '#E60023',
                    'description' => 'Connect your Pinterest account to create pins',
                    'route' => 'oauth.pinterest.redirect',
                    'disabled' => !$pinterestConfigured,
                    'requiresConfig' => !$pinterestConfigured,
                ],
                'tiktok' => [
                    'name' => 'TikTok',
                    'icon' => 'fab fa-tiktok',
                    'color' => '#000000',
                    'description' => 'Connect your TikTok account (video content only)',
                    'route' => 'oauth.tiktok.redirect',
                    'disabled' => !$tiktokConfigured,
                    'requiresConfig' => !$tiktokConfigured,
                ],
                'instagram' => [
                    'name' => 'Instagram',
                    'icon' => 'fab fa-instagram',
                    'color' => '#E4405F',
                    'description' => 'Connect via Facebook Business (requires Facebook Page)',
                    'disabled' => true,
                    'comingSoon' => true,
                ],
            ],
        ]);
    }
}
