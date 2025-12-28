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

class ContractEdit extends Component
{
    use WithFileUploads;

    public Contract $contract;

    public bool $editable = true;

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

    // AI enhancement prompt
    public string $aiPrompt = '';

    public function mount(Contract $contract): void
    {
        $this->contract = $contract->load('client');

        // Staff can only access contracts for assigned clients
        $user = auth()->user();
        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            if (! in_array((int) $this->contract->client_id, $allowed, true)) {
                abort(403, 'You do not have access to this contract.');
            }
        }

        $this->editable = in_array($this->contract->status, ['draft', 'pending_signature'], true);

        $this->title = $this->contract->title;
        $this->description = (string) $this->contract->description;
        $this->start_date = $this->contract->start_date?->format('Y-m-d');
        $this->end_date = $this->contract->end_date?->format('Y-m-d');
        $this->value = (string) ($this->contract->value ?? 0);
        $this->status = $this->contract->status;

        $meta = $this->contract->meta ?? [];
        $this->project_id = $meta['project_id'] ?? null;
        $this->request_id = $meta['request_id'] ?? null;
    }

    protected function rules(): array
    {
        return [
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

    public function toggleAiPanel(): void
    {
        $this->showAiPanel = ! $this->showAiPanel;
    }

    public function generateWithAi(ContractGeneratorService $svc): void
    {
        if (! $this->templateId) {
            session()->flash('error', 'Please select a template.');
            return;
        }

        $client = $this->contract->client;
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

    public function applyAiPdf(): void
    {
        if ($this->aiHtml === '') {
            session()->flash('error', 'No AI content to apply.');
            return;
        }

        $client = $this->contract->client;

        $pdf = Pdf::loadView('documents.contract-template', [
            'appName' => config('app.name'),
            'client' => $client,
            'contract' => $this->contract,
            'html' => $this->aiHtml,
            'generatedAt' => now(),
        ]);

        $bytes = $pdf->output();
        $path = 'generated/' . $this->contract->contract_number . '-' . Str::slug($this->contract->title) . '-v' . time() . '.pdf';
        Storage::disk('contracts')->put($path, $bytes);
        $this->contract->update(['file_path' => $path]);

        $this->aiHtml = '';
        $this->useAiGenerated = false;
        $this->contract->refresh();

        session()->flash('success', 'AI-generated PDF applied to contract.');
    }

    public function save(): void
    {
        if (! $this->editable) {
            session()->flash('error', 'This contract can no longer be edited.');
            return;
        }

        $data = $this->validate();
        $client = $this->contract->client;

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

        $this->contract->update([
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'value' => (float) $data['value'],
            'status' => $data['status'],
            'meta' => array_merge($this->contract->meta ?? [], [
                'project_id' => $data['project_id'],
                'request_id' => $data['request_id'],
            ]),
        ]);

        // Handle new file upload
        if ($this->contractFile) {
            $path = $this->contractFile->store('uploads', 'contracts');
            $this->contract->update(['file_path' => $path]);
        }

        $this->contract->refresh();

        // Update editable status based on new status
        $this->editable = in_array($this->contract->status, ['draft', 'pending_signature'], true);

        ActivityLog::log(
            "Admin updated contract: {$this->contract->contract_number}",
            $this->contract,
            null,
            'updated',
            'contracts'
        );

        session()->flash('success', 'Contract updated successfully.');
    }

    public function sendForSignature(): void
    {
        if ($this->contract->status !== 'draft') {
            session()->flash('error', 'Only draft contracts can be sent for signature.');
            return;
        }

        $this->contract->update(['status' => 'pending_signature']);
        $this->contract->refresh();
        $this->status = 'pending_signature';

        ActivityLog::log(
            "Contract sent for signature: {$this->contract->contract_number}",
            $this->contract,
            null,
            'updated',
            'contracts'
        );

        session()->flash('success', 'Contract sent for signature.');
    }

    public function activateContract(): void
    {
        if (! in_array($this->contract->status, ['draft', 'pending_signature'], true)) {
            session()->flash('error', 'Cannot activate this contract.');
            return;
        }

        $this->contract->update([
            'status' => 'active',
            'signed_at' => now(),
            'signed_by' => 'Admin (' . auth()->user()->name . ')',
        ]);
        $this->contract->refresh();
        $this->status = 'active';
        $this->editable = false;

        ActivityLog::log(
            "Contract activated: {$this->contract->contract_number}",
            $this->contract,
            null,
            'updated',
            'contracts'
        );

        session()->flash('success', 'Contract activated.');
    }

    public function terminateContract(): void
    {
        if ($this->contract->status === 'terminated') {
            session()->flash('error', 'Contract is already terminated.');
            return;
        }

        $this->contract->update(['status' => 'terminated']);
        $this->contract->refresh();
        $this->status = 'terminated';
        $this->editable = false;

        ActivityLog::log(
            "Contract terminated: {$this->contract->contract_number}",
            $this->contract,
            null,
            'terminated',
            'contracts'
        );

        session()->flash('success', 'Contract terminated.');
    }

    public function deleteContract()
    {
        $contractNumber = $this->contract->contract_number;
        $this->contract->delete();

        ActivityLog::log(
            "Contract deleted: {$contractNumber}",
            null,
            ['contract_number' => $contractNumber],
            'deleted',
            'contracts'
        );

        session()->flash('success', 'Contract deleted.');

        return redirect()->route('admin.contracts.index');
    }

    public function render()
    {
        $client = $this->contract->client;

        $projects = Project::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'title']);

        $requests = ServiceRequest::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'title']);

        $templates = DocumentTemplate::query()
            ->where('category', 'contract')
            ->orWhere('category', 'like', '%contract%')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'category']);

        return view('livewire.admin.contracts.edit', [
            'contract' => $this->contract->fresh('client'),
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
        ])->layout('layouts.admin', ['title' => 'Edit Contract: ' . $this->contract->title]);
    }
}
