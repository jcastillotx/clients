<?php

namespace App\Http\Livewire\Client;

use App\Models\AnalyticsAccount;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AccountConnections extends Component
{
    public string $activeTab = 'overview';
    public bool $showSocialGuide = false;
    public string $selectedSocialPlatform = '';

    protected $queryString = ['activeTab'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->isClient(), 403);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function showSocialSetup(string $platform): void
    {
        $this->selectedSocialPlatform = $platform;
        $this->showSocialGuide = true;
    }

    public function closeSocialGuide(): void
    {
        $this->showSocialGuide = false;
        $this->selectedSocialPlatform = '';
    }

    public function getClientProperty()
    {
        return Auth::user()->client;
    }

    public function getGscConnectedProperty(): bool
    {
        return !empty($this->client?->gsc_refresh_token);
    }

    public function getGscSitesProperty(): array
    {
        return $this->client?->meta['gsc_sites'] ?? [];
    }

    public function getGscEmailProperty(): ?string
    {
        return $this->client?->meta['gsc_email'] ?? null;
    }

    public function getSocialAccountsProperty()
    {
        $clientId = Auth::user()->client_id;
        return SocialAccount::where('client_id', $clientId)
            ->where('is_connected', true)
            ->get()
            ->keyBy('platform');
    }

    public function getAnalyticsAccountsProperty()
    {
        $clientId = Auth::user()->client_id;
        return AnalyticsAccount::where('client_id', $clientId)
            ->where('is_connected', true)
            ->get();
    }

    public function getSocialPlatformsProperty(): array
    {
        return [
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook',
                'color' => '#1877F2',
                'description' => 'Connect your Facebook Page to schedule posts and track engagement.',
                'features' => ['Post scheduling', 'Page insights', 'Engagement tracking'],
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => 'fab fa-instagram',
                'color' => '#E4405F',
                'description' => 'Connect your Instagram Business account for visual content management.',
                'features' => ['Photo/video posts', 'Story scheduling', 'Hashtag analytics'],
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'icon' => 'fab fa-linkedin',
                'color' => '#0A66C2',
                'description' => 'Connect your LinkedIn Page for professional content distribution.',
                'features' => ['Company page posts', 'Professional networking', 'B2B engagement'],
            ],
            'twitter' => [
                'name' => 'X (Twitter)',
                'icon' => 'fab fa-x-twitter',
                'color' => '#000000',
                'description' => 'Connect your X account for real-time engagement and updates.',
                'features' => ['Tweet scheduling', 'Thread creation', 'Mention monitoring'],
            ],
            'pinterest' => [
                'name' => 'Pinterest',
                'icon' => 'fab fa-pinterest',
                'color' => '#E60023',
                'description' => 'Connect Pinterest for visual discovery and traffic generation.',
                'features' => ['Pin scheduling', 'Board management', 'Rich pins'],
            ],
            'tiktok' => [
                'name' => 'TikTok',
                'icon' => 'fab fa-tiktok',
                'color' => '#000000',
                'description' => 'Connect TikTok for short-form video content management.',
                'features' => ['Video uploads', 'Trend tracking', 'Analytics'],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.client.account-connections', [
            'client' => $this->client,
            'gscConnected' => $this->gscConnected,
            'gscSites' => $this->gscSites,
            'gscEmail' => $this->gscEmail,
            'socialAccounts' => $this->socialAccounts,
            'analyticsAccounts' => $this->analyticsAccounts,
            'socialPlatforms' => $this->socialPlatforms,
        ])->layout('layouts.app', ['title' => 'Manage Connections']);
    }
}
