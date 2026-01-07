<?php

namespace App\Http\Livewire\Ads;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Client;
use App\Services\Ads\FacebookAdsService;
use App\Services\Ads\GoogleAdsService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AdCampaignManager extends Component
{
    use WithPagination;

    public $clientId;
    public $client;
    public $showCreateModal = false;
    public $statusFilter = 'all';
    public $platformFilter = 'all';

    public $campaignId = null;
    public $adAccountId;
    public $name = '';
    public $objective = 'conversions';
    public $status = 'draft';
    public $dailyBudget;
    public $lifetimeBudget;
    public $startDate;
    public $endDate;
    public $targetAudience = '';

    protected $rules = [
        'adAccountId' => 'required|exists:ad_accounts,id',
        'name' => 'required|string|max:255',
        'objective' => 'required|in:awareness,consideration,conversions,traffic,engagement,app_installs,video_views,lead_generation,messages,sales',
        'status' => 'required|in:draft,active,paused',
        'dailyBudget' => 'nullable|numeric|min:1',
        'lifetimeBudget' => 'nullable|numeric|min:1',
        'startDate' => 'nullable|date',
        'endDate' => 'nullable|date|after:startDate',
    ];

    public function mount($clientId = null)
    {
        $this->clientId = $clientId ?? auth()->user()->client_id;
        $this->client = Client::findOrFail($this->clientId);
    }

    public function openCreateModal()
    {
        $this->reset(['campaignId', 'name', 'objective', 'status', 'dailyBudget', 'lifetimeBudget', 'startDate', 'endDate', 'targetAudience']);
        $this->startDate = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function editCampaign($campaignId)
    {
        $campaign = AdCampaign::where('client_id', $this->clientId)->findOrFail($campaignId);

        $this->campaignId = $campaign->id;
        $this->adAccountId = $campaign->ad_account_id;
        $this->name = $campaign->name;
        $this->objective = $campaign->objective;
        $this->status = $campaign->status;
        $this->dailyBudget = $campaign->daily_budget;
        $this->lifetimeBudget = $campaign->lifetime_budget;
        $this->startDate = $campaign->start_date?->format('Y-m-d');
        $this->endDate = $campaign->end_date?->format('Y-m-d');
        $this->targetAudience = $campaign->target_audience;

        $this->showCreateModal = true;
    }

    public function saveCampaign()
    {
        $this->validate();

        $data = [
            'client_id' => $this->clientId,
            'ad_account_id' => $this->adAccountId,
            'name' => $this->name,
            'objective' => $this->objective,
            'status' => $this->status,
            'daily_budget' => $this->dailyBudget,
            'lifetime_budget' => $this->lifetimeBudget,
            'start_date' => $this->startDate ? Carbon::parse($this->startDate) : null,
            'end_date' => $this->endDate ? Carbon::parse($this->endDate) : null,
            'target_audience' => $this->targetAudience,
            'created_by' => auth()->id(),
        ];

        if ($this->campaignId) {
            $campaign = AdCampaign::findOrFail($this->campaignId);
            $campaign->update($data);
            session()->flash('message', 'Campaign updated successfully!');
        } else {
            $campaign = AdCampaign::create($data);

            if ($this->status === 'active') {
                try {
                    $this->publishCampaign($campaign);
                } catch (\Exception $e) {
                    session()->flash('error', 'Campaign created but failed to publish: ' . $e->getMessage());
                }
            }

            session()->flash('message', 'Campaign created successfully!');
        }

        $this->showCreateModal = false;
        $this->reset(['campaignId', 'name', 'objective', 'status', 'dailyBudget', 'lifetimeBudget', 'startDate', 'endDate']);
    }

    public function deleteCampaign($campaignId)
    {
        AdCampaign::where('client_id', $this->clientId)
            ->findOrFail($campaignId)
            ->delete();

        session()->flash('message', 'Campaign deleted successfully!');
    }

    public function toggleCampaignStatus($campaignId)
    {
        $campaign = AdCampaign::where('client_id', $this->clientId)->findOrFail($campaignId);

        $newStatus = $campaign->status === 'active' ? 'paused' : 'active';

        $campaign->update(['status' => $newStatus]);

        if ($campaign->platform_campaign_id) {
            try {
                $service = $this->getAdService($campaign->adAccount);
                $service->updateCampaignStatus($campaign, $newStatus);
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to update campaign status: ' . $e->getMessage());
                return;
            }
        }

        session()->flash('message', 'Campaign status updated!');
    }

    public function publishCampaign(AdCampaign $campaign)
    {
        $adAccount = $campaign->adAccount;

        if (!$adAccount->is_connected) {
            throw new \Exception('Ad account not connected');
        }

        $service = $this->getAdService($adAccount);
        $result = $service->createCampaign($campaign);

        $campaign->update([
            'platform_campaign_id' => $result['id'] ?? null,
            'status' => 'active',
        ]);
    }

    protected function getAdService(AdAccount $adAccount)
    {
        return match ($adAccount->platform) {
            'google_ads' => new GoogleAdsService($adAccount),
            'facebook_ads', 'instagram_ads' => new FacebookAdsService($adAccount),
            default => throw new \Exception('Unsupported ad platform'),
        };
    }

    public function render()
    {
        $adAccounts = AdAccount::where('client_id', $this->clientId)
            ->where('is_connected', true)
            ->get();

        $campaigns = AdCampaign::where('client_id', $this->clientId)
            ->with(['adAccount', 'createdBy'])
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->platformFilter !== 'all', function ($query) {
                $query->whereHas('adAccount', function ($q) {
                    $q->where('platform', $this->platformFilter);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.ads.ad-campaign-manager', [
            'adAccounts' => $adAccounts,
            'campaigns' => $campaigns,
        ]);
    }
}
