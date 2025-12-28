<?php

namespace App\Http\Livewire\Admin\Contracts;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentTemplate;
use App\Models\Project;
use App\Models\Request as ServiceRequest;
use App\Services\AI\ContractGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContractCreate extends Component
{
    use WithFileUploads;

    public ?int $client_id = null;

    public string $title = '';

    public string $description = '';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $value = '0';

    public string $status = 'draft';

    public ?int $project_id = null;

    public ?int $request_id = null;

    // File upload
    public $contractFile = null;

    // AI generation
    public bool $showAiPanel = false;

    public ?int $templateId = null;

    public string $aiHtml = '';

    public bool $useAiGenerated = false;

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->end_date = now()->addYear()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'value' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'pending_signature', 'active', 'expired', 'terminated'])],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'request_id' => ['nullable', 'integer', Rule::exists('requests', 'id')],
            'contractFile' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'client_id') {
            $this->project_id = null;
            $this->request_id = null;
        }
    }

    public function toggleAiPanel(): void
    {
        $this->showAiPanel = ! $this->showAiPanel;
    }

    public function generateWithAi(ContractGeneratorService $svc): void
    {
        if (! $this->client_id) {
            session()->flash('error', 'Please select a client first.');
            return;
        }

        if (! $this->templateId) {
            session()->flash('error', 'Please select a template.');
            return;
        }

        $client = Client::query()->findOrFail($this->client_id);
        $project = $this->project_id ? Project::query()->find($this->project_id) : null;
        $request = $this->request_id ? ServiceRequest::query()->find($this->request_id) : null;
        $template = DocumentTemplate::query()->findOrFail($this->templateId);

        try {
            $res = $svc->generateContract($client, $project, $request, $template, [
                'executed_by' => auth()->id(),
                'provider' => 'perplexity',
            ]);

            $this->aiHtml = $res['html'];
            $this->useAiGenerated = true;

            if ($this->title === '') {
                $this->title = 'Contract — ' . $client->company_name;
            }

            session()->flash('success', 'AI draft generated! Review the content and save the contract.');
        } catch (\Throwable $e) {
            session()->flash('error', 'AI generation failed: ' . $e->getMessage());
        }
    }

    public function clearAiDraft(): void
    {
        $this->aiHtml = '';
        $this->useAiGenerated = false;
    }

    public function save()
    {
        $data = $this->validate();
        $client = Client::query()->findOrFail((int) $data['client_id']);

        // Validate project/request belong to client
        if ($data['project_id']) {
            $proj = Project::query()->where('client_id', $client->id)->find($data['project_id']);
            if (! $proj) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'project_id' => 'Selected project does not belong to this client.',
                ]);
            }
        }
        if ($data['request_id']) {
            $req = ServiceRequest::query()->where('client_id', $client->id)->find($data['request_id']);
            if (! $req) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'request_id' => 'Selected request does not belong to this client.',
                ]);
            }
        }

        $contract = Contract::create([
            'client_id' => $client->id,
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'value' => (float) $data['value'],
            'status' => $data['status'],
            'meta' => [
                'project_id' => $data['project_id'],
                'request_id' => $data['request_id'],
            ],
        ]);

        // Handle file upload or AI-generated PDF
        if ($this->useAiGenerated && $this->aiHtml !== '') {
            $pdf = Pdf::loadView('documents.contract-template', [
                'appName' => config('app.name'),
                'client' => $client,
                'contract' => $contract,
                'html' => $this->aiHtml,
                'generatedAt' => now(),
            ]);

            $bytes = $pdf->output();
            $path = 'generated/' . $contract->contract_number . '-' . Str::slug($contract->title) . '.pdf';
            Storage::disk('contracts')->put($path, $bytes);
            $contract->update(['file_path' => $path]);
        } elseif ($this->contractFile) {
            $path = $this->contractFile->store('uploads', 'contracts');
            $contract->update(['file_path' => $path]);
        }

        ActivityLog::log(
            "Admin created contract: {$contract->contract_number}",
            $contract,
            ['client_id' => $client->id, 'status' => $contract->status],
            'created',
            'contracts'
        );

        session()->flash('success', 'Contract created successfully.');

        return redirect()->route('admin.contracts.edit', $contract);
    }

    public function render()
    {
        $user = auth()->user();
        $staffClientIds = [];
        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $staffClientIds = $user->assignedClientIds();
        }

        $clients = Client::query()
            ->when(! empty($staffClientIds), fn ($q) => $q->whereIn('id', $staffClientIds))
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        $projects = collect();
        $requests = collect();
        if ($this->client_id) {
            $projects = Project::query()
                ->where('client_id', $this->client_id)
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'title']);
            $requests = ServiceRequest::query()
                ->where('client_id', $this->client_id)
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'title']);
        }

        $templates = DocumentTemplate::query()
            ->where('category', 'contract')
            ->orWhere('category', 'like', '%contract%')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'category']);

        return view('livewire.admin.contracts.create', [
            'clients' => $clients,
            'projects' => $projects,
            'requests' => $requests,
            'templates' => $templates,
            'statuses' => [
                'draft' => 'Draft',
                'pending_signature' => 'Pending Signature',
                'active' => 'Active',
                'expired' => 'Expired',
                'terminated' => 'Terminated',
            ],
        ])->layout('layouts.admin', ['title' => 'Create Contract']);
    }
}
