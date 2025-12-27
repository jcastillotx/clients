<?php

namespace App\Http\Livewire\Admin\Contracts;

use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentTemplate;
use App\Models\Project;
use App\Models\Request as ServiceRequest;
use App\Services\AI\ContractGeneratorService;
use Livewire\Component;

class ContractGenerator extends Component
{
    public ?int $clientId = null;

    public ?int $projectId = null;

    public ?int $requestId = null;

    public ?int $templateId = null;

    public string $title = '';

    public string $html = '';

    public ?int $contractId = null;

    public function generate(ContractGeneratorService $svc): void
    {
        $client = Client::query()->findOrFail($this->clientId);
        $project = $this->projectId ? Project::query()->find($this->projectId) : null;
        $request = $this->requestId ? ServiceRequest::query()->find($this->requestId) : null;
        $template = DocumentTemplate::query()->findOrFail($this->templateId);

        $res = $svc->generateContract($client, $project, $request, $template, [
            'executed_by' => auth()->id(),
            'provider' => 'perplexity',
        ]);

        $this->title = $this->title !== '' ? $this->title : ('Contract — '.$client->company_name);
        $this->html = $res['html'];

        session()->flash('success', 'Contract draft generated.');
    }

    public function createContract(ContractGeneratorService $svc): void
    {
        $client = Client::query()->findOrFail($this->clientId);
        if (trim($this->title) === '' || trim($this->html) === '') {
            session()->flash('error', 'Generate a draft first.');

            return;
        }

        $contract = $svc->createContractFromHtml($client, trim($this->title), $this->html);
        $this->contractId = $contract->id;

        session()->flash('success', 'Contract PDF created (pending signature).');
    }

    public function render()
    {
        $clients = Client::query()->orderBy('company_name')->limit(200)->get(['id', 'company_name']);
        $projects = $this->clientId ? Project::query()->where('client_id', $this->clientId)->orderByDesc('id')->limit(200)->get(['id', 'title']) : collect();
        $requests = $this->clientId ? ServiceRequest::query()->where('client_id', $this->clientId)->orderByDesc('id')->limit(200)->get(['id', 'title']) : collect();
        $templates = DocumentTemplate::query()->orderBy('name')->limit(200)->get(['id', 'name', 'category']);

        return view('livewire.documents.contract-generator', [
            'clients' => $clients,
            'projects' => $projects,
            'requests' => $requests,
            'templates' => $templates,
            'createdContract' => $this->contractId ? Contract::query()->find($this->contractId) : null,
        ])->layout('layouts.admin', ['title' => 'Contract Generator']);
    }
}
