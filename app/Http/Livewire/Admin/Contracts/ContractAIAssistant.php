<?php

namespace App\Http\Livewire\Admin\Contracts;

use App\Models\AiTask;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentTemplate;
use App\Models\Project;
use App\Models\Request as ServiceRequest;
use App\Services\AI\AIProviderManager;
use Livewire\Component;

class ContractAIAssistant extends Component
{
    public ?int $contractId = null;

    public ?int $clientId = null;

    public ?int $projectId = null;

    public ?int $requestId = null;

    public ?int $templateId = null;

    public string $prompt = '';

    public string $assistantResponse = '';

    public bool $isLoading = false;

    public string $mode = 'generate'; // generate, review, improve, clause

    protected $listeners = [
        'setContractContext' => 'setContext',
    ];

    public function setContext(array $context): void
    {
        $this->contractId = $context['contractId'] ?? null;
        $this->clientId = $context['clientId'] ?? null;
        $this->projectId = $context['projectId'] ?? null;
        $this->requestId = $context['requestId'] ?? null;
    }

    public function askAi(AIProviderManager $providers): void
    {
        if (trim($this->prompt) === '') {
            session()->flash('error', 'Please enter a prompt.');
            return;
        }

        $this->isLoading = true;

        try {
            $context = $this->buildContext();

            $systemPrompt = $this->getSystemPrompt();
            $userPrompt = $this->getUserPrompt($context);

            $task = AiTask::create([
                'task_type' => 'contract_assistant',
                'input_data' => [
                    'mode' => $this->mode,
                    'prompt' => $this->prompt,
                    'contract_id' => $this->contractId,
                    'client_id' => $this->clientId,
                ],
                'status' => 'processing',
                'executed_by' => auth()->id(),
            ]);

            $response = $providers->withFallback('openai', function ($provider) use ($systemPrompt, $userPrompt, $task) {
                return $provider->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ], [
                    'task_type' => 'contract_assistant',
                    'timeout' => 120,
                    'task_id' => $task->id,
                ]);
            }, 'contract_assistant');

            $this->assistantResponse = (string) ($response['text'] ?? 'No response from AI.');

            $task->update([
                'output_data' => ['response' => $this->assistantResponse],
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        } catch (\Throwable $e) {
            $this->assistantResponse = 'Error: ' . $e->getMessage();
        }

        $this->isLoading = false;
    }

    public function suggestClauses(AIProviderManager $providers): void
    {
        $this->mode = 'clause';
        $this->prompt = 'Suggest appropriate legal clauses for this contract based on the client and context provided.';
        $this->askAi($providers);
    }

    public function reviewContract(AIProviderManager $providers): void
    {
        $this->mode = 'review';
        $this->prompt = 'Review the current contract for potential issues, missing clauses, or improvements.';
        $this->askAi($providers);
    }

    public function clearResponse(): void
    {
        $this->assistantResponse = '';
        $this->prompt = '';
    }

    protected function buildContext(): array
    {
        $context = [];

        if ($this->contractId) {
            $contract = Contract::with('client')->find($this->contractId);
            if ($contract) {
                $context['contract'] = [
                    'title' => $contract->title,
                    'description' => $contract->description,
                    'value' => $contract->value,
                    'status' => $contract->status,
                    'start_date' => $contract->start_date?->format('Y-m-d'),
                    'end_date' => $contract->end_date?->format('Y-m-d'),
                ];
                $context['client'] = [
                    'name' => $contract->client?->company_name,
                    'industry' => $contract->client?->industry,
                    'tier' => $contract->client?->tier,
                ];
            }
        } elseif ($this->clientId) {
            $client = Client::find($this->clientId);
            if ($client) {
                $context['client'] = [
                    'name' => $client->company_name,
                    'industry' => $client->industry,
                    'tier' => $client->tier,
                ];
            }
        }

        if ($this->projectId) {
            $project = Project::find($this->projectId);
            if ($project) {
                $context['project'] = [
                    'title' => $project->title,
                    'description' => $project->description,
                ];
            }
        }

        if ($this->requestId) {
            $request = ServiceRequest::find($this->requestId);
            if ($request) {
                $context['request'] = [
                    'title' => $request->title,
                    'description' => $request->description,
                    'type' => $request->type,
                ];
            }
        }

        if ($this->templateId) {
            $template = DocumentTemplate::find($this->templateId);
            if ($template) {
                $context['template'] = [
                    'name' => $template->name,
                    'category' => $template->category,
                ];
            }
        }

        return $context;
    }

    protected function getSystemPrompt(): string
    {
        $basePrompt = "You are an expert legal and business contract assistant. You help draft, review, and improve business contracts. 
Your responses should be professional, legally sound, and tailored to the specific business context provided.
Always consider industry best practices and common contract terms.
When suggesting clauses, provide clear, actionable language that can be directly used in contracts.";

        return match ($this->mode) {
            'generate' => $basePrompt . "\nFocus on generating new contract content based on the user's requirements.",
            'review' => $basePrompt . "\nFocus on reviewing and identifying potential issues, gaps, or improvements in the contract.",
            'improve' => $basePrompt . "\nFocus on improving and enhancing existing contract language.",
            'clause' => $basePrompt . "\nFocus on suggesting specific legal clauses appropriate for the contract type and context.",
            default => $basePrompt,
        };
    }

    protected function getUserPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT);

        return "Context:\n{$contextJson}\n\nUser Request:\n{$this->prompt}";
    }

    public function render()
    {
        $templates = DocumentTemplate::query()
            ->where('category', 'contract')
            ->orWhere('category', 'like', '%contract%')
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'category']);

        return view('livewire.admin.contracts.ai-assistant', [
            'templates' => $templates,
            'modes' => [
                'generate' => 'Generate Content',
                'review' => 'Review Contract',
                'improve' => 'Improve Language',
                'clause' => 'Suggest Clauses',
            ],
        ]);
    }
}
