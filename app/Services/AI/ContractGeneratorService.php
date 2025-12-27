<?php

namespace App\Services\AI;

use App\Models\AiTask;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentTemplate;
use App\Models\Project;
use App\Models\Request as ServiceRequest;
use App\Services\AI\Prompts\ContractGeneratorPrompt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractGeneratorService
{
    public function __construct(
        protected AIProviderManager $providers
    ) {}

    /**
     * Generate contract HTML from a template body + context.
     * Uses Perplexity by default (research-backed drafting).
     *
     * @return array{html:string, meta:array<string,mixed>}
     */
    public function generateContract(Client $client, ?Project $project, ?ServiceRequest $request, DocumentTemplate $template, array $options = []): array
    {
        $task = AiTask::create([
            'task_type' => 'generate_contract',
            'input_data' => [
                'client_id' => $client->id,
                'project_id' => $project?->id,
                'request_id' => $request?->id,
                'template_id' => $template->id,
                'client_tier' => $client->tier,
            ],
            'status' => 'processing',
            'executed_by' => $options['executed_by'] ?? null,
        ]);

        $system = ContractGeneratorPrompt::systemPrompt();
        $user = ContractGeneratorPrompt::userPrompt($client, $project, $request, $template);

        $preferred = (string) ($options['provider'] ?? 'perplexity');
        $model = (string) ($options['model'] ?? '');

        $res = $this->providers->withFallback($preferred, function ($provider) use ($system, $user, $client, $model, $task) {
            return $provider->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], [
                'task_type' => 'generate_contract',
                'client_id' => $client->id,
                'timeout' => 180,
                'task_id' => $task->id,
                'model' => $model !== '' ? $model : null,
            ]);
        }, 'generate_contract');

        $data = $this->parseJsonFromText((string) ($res['text'] ?? ''));
        $html = (string) ($data['html'] ?? '');
        if (trim($html) === '') {
            $html = '<p>(AI did not return contract HTML.)</p>';
        }

        $task->update([
            'output_data' => $data,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return [
            'html' => $html,
            'meta' => [
                'task_id' => $task->id,
                'provider' => $res['provider'] ?? null,
                'model' => $res['model'] ?? null,
                'estimated_cost' => $res['estimated_cost'] ?? null,
            ],
        ];
    }

    /**
     * Persist a generated contract as a Contract model + PDF.
     */
    public function createContractFromHtml(Client $client, string $title, string $html, array $options = []): Contract
    {
        $contract = Contract::create([
            'client_id' => $client->id,
            'title' => $title,
            'description' => $options['description'] ?? 'AI-generated contract draft.',
            'value' => (float) ($options['value'] ?? 0),
            'status' => 'pending_signature',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
        ]);

        $pdf = Pdf::loadView('documents.contract-template', [
            'appName' => config('app.name'),
            'client' => $client,
            'contract' => $contract,
            'html' => $html,
            'generatedAt' => now(),
        ]);

        $bytes = $pdf->output();
        $path = 'generated/'.$contract->contract_number.'-'.Str::slug($title).'.pdf';
        Storage::disk('contracts')->put($path, $bytes);
        $contract->update(['file_path' => $path]);

        return $contract;
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseJsonFromText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
