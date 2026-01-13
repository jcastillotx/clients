<?php

namespace App\Http\Livewire\Marketing;

use App\Models\Client;
use App\Models\CompetitorAnalysis;
use App\Services\Marketing\CompetitorAnalysisService;
use Livewire\Component;
use Livewire\WithPagination;

class CompetitorAnalysisDashboard extends Component
{
    use WithPagination;

    public $clientId;
    public $client;

    // Form inputs
    public $competitorName = '';
    public $competitorUrl = '';
    public $competitorIndustry = '';
    public $analysisType = 'full'; // full or quick

    // View state
    public $selectedAnalysis = null;
    public $showForm = false;
    public $activeTab = 'overview';
    public $searchTerm = '';
    public $statusFilter = 'all';

    // Processing state
    public $isProcessing = false;

    protected $queryString = [
        'searchTerm' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    protected $rules = [
        'competitorName' => 'required|string|min:2|max:255',
        'competitorUrl' => 'nullable|url|max:500',
        'competitorIndustry' => 'nullable|string|max:255',
        'analysisType' => 'required|in:full,quick',
    ];

    protected $messages = [
        'competitorName.required' => 'Please enter the competitor name.',
        'competitorName.min' => 'Competitor name must be at least 2 characters.',
        'competitorUrl.url' => 'Please enter a valid URL.',
    ];

    public function mount($clientId = null)
    {
        $this->clientId = $clientId ?? auth()->user()->client_id;
        $this->client = Client::findOrFail($this->clientId);
        $this->competitorIndustry = $this->client->industry ?? '';
    }

    public function startNewAnalysis()
    {
        $this->reset(['competitorName', 'competitorUrl', 'selectedAnalysis']);
        $this->competitorIndustry = $this->client->industry ?? '';
        $this->analysisType = 'full';
        $this->showForm = true;
    }

    public function cancelForm()
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function runAnalysis()
    {
        $this->validate();

        $this->isProcessing = true;

        try {
            $service = app(CompetitorAnalysisService::class);

            if ($this->analysisType === 'quick') {
                $analysis = $service->quickAnalyze(
                    $this->client,
                    $this->competitorName,
                    $this->competitorUrl ?: null,
                    auth()->id()
                );
            } else {
                $analysis = $service->analyze(
                    $this->client,
                    $this->competitorName,
                    $this->competitorUrl ?: null,
                    $this->competitorIndustry ?: null,
                    auth()->id()
                );
            }

            $this->isProcessing = false;
            $this->showForm = false;
            $this->selectedAnalysis = $analysis->id;
            $this->activeTab = 'overview';

            if ($analysis->isComplete()) {
                session()->flash('message', "Competitor analysis for {$this->competitorName} completed successfully!");
            } else {
                session()->flash('error', 'Analysis could not be completed. Please try again.');
            }

            $this->reset(['competitorName', 'competitorUrl']);

        } catch (\Exception $e) {
            $this->isProcessing = false;
            session()->flash('error', 'Error running analysis: ' . $e->getMessage());
        }
    }

    public function viewAnalysis($analysisId)
    {
        $this->selectedAnalysis = $analysisId;
        $this->activeTab = 'overview';
        $this->showForm = false;
    }

    public function closeAnalysis()
    {
        $this->selectedAnalysis = null;
    }

    public function deleteAnalysis($analysisId)
    {
        $analysis = CompetitorAnalysis::where('client_id', $this->clientId)->findOrFail($analysisId);
        $competitorName = $analysis->competitor_name;
        $analysis->delete();

        if ($this->selectedAnalysis === $analysisId) {
            $this->selectedAnalysis = null;
        }

        session()->flash('message', "Analysis for {$competitorName} has been deleted.");
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $analyses = CompetitorAnalysis::where('client_id', $this->clientId)
            ->when($this->searchTerm, function ($query) {
                $query->where('competitor_name', 'like', '%' . $this->searchTerm . '%');
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $currentAnalysis = null;
        if ($this->selectedAnalysis) {
            $currentAnalysis = CompetitorAnalysis::where('client_id', $this->clientId)
                ->find($this->selectedAnalysis);
        }

        $stats = [
            'total' => CompetitorAnalysis::where('client_id', $this->clientId)->count(),
            'completed' => CompetitorAnalysis::where('client_id', $this->clientId)->completed()->count(),
            'processing' => CompetitorAnalysis::where('client_id', $this->clientId)->where('status', 'processing')->count(),
        ];

        return view('livewire.marketing.competitor-analysis-dashboard', [
            'analyses' => $analyses,
            'currentAnalysis' => $currentAnalysis,
            'stats' => $stats,
        ]);
    }
}
