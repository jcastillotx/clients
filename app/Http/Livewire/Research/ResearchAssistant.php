<?php

namespace App\Http\Livewire\Research;

use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Services\AI\ResearchAssistantService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ResearchAssistant extends Component
{
    public string $mode = 'research'; // research|competitive|market|content|qa|creative|project

    public string $topic = '';

    public string $depth = 'standard';

    public ?string $region = null;

    // competitive
    public ?int $clientId = null;

    public string $competitorsCsv = '';

    // market
    public string $industry = '';

    // content
    public string $audience = '';

    // Q&A
    public string $question = '';

    public ?int $requestId = null;

    // creative
    public string $briefJson = '{"brand":"","goal":"","audience":"","tone":"","channels":["email","social","search"],"constraints":""}';

    public array $result = [];

    public ?string $error = null;

    public function run(ResearchAssistantService $svc): void
    {
        $this->error = null;
        $this->result = [];

        try {
            if ($this->mode === 'research') {
                $this->result = $svc->conductResearch($this->topic, $this->depth, ['region' => $this->region]);

                return;
            }

            if ($this->mode === 'competitive') {
                $client = Client::query()->findOrFail($this->clientId);
                $competitors = array_filter(array_map('trim', explode(',', $this->competitorsCsv)));
                $report = $svc->competitiveAnalysis($client, $competitors);
                $this->result = $report->payload ?? [];

                return;
            }

            if ($this->mode === 'market') {
                $report = $svc->marketAnalysis($this->industry, $this->region);
                $this->result = $report->payload ?? [];

                return;
            }

            if ($this->mode === 'content') {
                $this->result = $svc->contentResearch($this->topic, $this->audience, ['region' => $this->region]);

                return;
            }

            if ($this->mode === 'qa') {
                $ctx = [
                    'client_id' => Auth::user()?->client_id,
                    'asked_by' => Auth::id(),
                    'request_id' => $this->requestId,
                    'topic' => $this->topic ?: null,
                    'category' => 'business',
                ];
                $q = $svc->answerClientQuestion($this->question, $ctx);
                $this->result = [
                    'question_id' => $q->id,
                    'answer' => $q->answer,
                    'sources' => $q->sources,
                    'is_opportunity' => $q->is_opportunity,
                    'opportunity_type' => $q->opportunity_type,
                ];

                return;
            }

            if ($this->mode === 'creative') {
                $brief = json_decode($this->briefJson, true);
                if (! is_array($brief)) {
                    $brief = ['brief' => $this->briefJson];
                }
                $this->result = $svc->brainstorm($brief);

                return;
            }

            if ($this->mode === 'project') {
                $req = ServiceRequest::query()->findOrFail($this->requestId);
                $report = $svc->createResearchDossierForRequest($req, $this->topic, $this->depth, ['region' => $this->region]);
                $this->result = $report->payload ?? [];

                return;
            }
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        $clients = Client::query()->orderBy('company_name')->limit(200)->get(['id', 'company_name']);

        return view('livewire.research.research-assistant', [
            'clients' => $clients,
        ])->layout('layouts.app', ['title' => 'Research Assistant']);
    }
}
