<?php

namespace App\Http\Livewire\Marketing;

use App\Models\Client;
use App\Models\WebsiteAudit;
use App\Services\Marketing\WebsiteAuditorService;
use Livewire\Component;

class SeoAuditViewer extends Component
{
    public $clientId;
    public $client;
    public $selectedAuditId;
    public $audit;
    public $activeTab = 'overview';
    public $runningAudit = false;

    protected $queryString = [
        'selectedAuditId' => ['except' => null],
        'activeTab' => ['except' => 'overview'],
    ];

    public function mount($clientId = null, $auditId = null)
    {
        $this->clientId = $clientId ?? auth()->user()->client_id;
        $this->client = Client::findOrFail($this->clientId);

        if ($auditId) {
            $this->selectedAuditId = $auditId;
        } else {
            $latestAudit = WebsiteAudit::where('client_id', $this->clientId)
                ->latest()
                ->first();

            $this->selectedAuditId = $latestAudit?->id;
        }

        if ($this->selectedAuditId) {
            $this->loadAudit();
        }
    }

    public function loadAudit()
    {
        $this->audit = WebsiteAudit::with(['issues', 'pages'])
            ->where('client_id', $this->clientId)
            ->findOrFail($this->selectedAuditId);
    }

    public function selectAudit($auditId)
    {
        $this->selectedAuditId = $auditId;
        $this->loadAudit();
        $this->activeTab = 'overview';
    }

    public function runNewAudit()
    {
        if (!$this->client->website) {
            session()->flash('error', 'No website URL configured for this client.');
            return;
        }

        $this->runningAudit = true;

        try {
            $service = new WebsiteAuditorService();
            $audit = $service->auditWebsite(
                $this->client,
                $this->client->website,
                ['max_depth' => 3, 'max_pages' => 100]
            );

            $this->selectedAuditId = $audit->id;
            $this->loadAudit();

            session()->flash('message', 'Website audit completed successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Audit failed: ' . $e->getMessage());
        } finally {
            $this->runningAudit = false;
        }
    }

    public function render()
    {
        $audits = WebsiteAudit::where('client_id', $this->clientId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $issuesByCategory = [];
        $issuesBySeverity = [];

        if ($this->audit) {
            $issuesByCategory = $this->audit->issues()
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category')
                ->toArray();

            $issuesBySeverity = $this->audit->issues()
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->get()
                ->pluck('count', 'severity')
                ->toArray();
        }

        return view('livewire.marketing.seo-audit-viewer', [
            'audits' => $audits,
            'issuesByCategory' => $issuesByCategory,
            'issuesBySeverity' => $issuesBySeverity,
        ]);
    }

    public function getScoreColorClass($score)
    {
        if ($score >= 90) return 'text-green-600';
        if ($score >= 70) return 'text-yellow-600';
        return 'text-red-600';
    }

    public function getScoreBgClass($score)
    {
        if ($score >= 90) return 'bg-green-100';
        if ($score >= 70) return 'bg-yellow-100';
        return 'bg-red-100';
    }
}
