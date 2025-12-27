<?php

namespace App\Services\AI;

use App\Models\AiTask;
use App\Models\Contract;
use App\Models\Request as ServiceRequest;
use App\Models\RequestEstimate;
use App\Services\AI\Prompts\SowDraftPrompt;
use App\Services\Estimates\CostCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SowGenerationService
{
    public function __construct(
        protected AIProviderManager $providers,
        protected CostCalculationService $costs
    ) {}

    /**
     * Draft SOW narrative sections via AI.
     *
     * @param  array<string,mixed>  $estimate
     * @param  array<string,mixed>  $pricing
     * @return array<string,string>
     */
    public function draftSowSections(ServiceRequest $request, array $estimate, array $pricing, array $options = []): array
    {
        $payloadRequest = [
            'id' => $request->id,
            'client' => $request->client?->company_name ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'priority' => $request->priority,
            'tier' => $request->client?->tier ?? null,
        ];

        $messages = [
            ['role' => 'system', 'content' => SowDraftPrompt::systemPrompt()],
            ['role' => 'user', 'content' => SowDraftPrompt::userPrompt($payloadRequest, $estimate, $pricing)],
        ];

        $task = AiTask::create([
            'task_type' => 'draft_sow',
            'input_data' => [
                'request_id' => $request->id,
            ],
            'status' => 'processing',
            'executed_by' => $options['executed_by'] ?? null,
        ]);

        try {
            $target = $this->providers->routeToOptimalProvider('generate_estimate', (string) ($options['complexity'] ?? 'medium'));
            $preferred = (string) ($options['provider'] ?? $target['provider'] ?? 'openai');
            $model = (string) ($options['model'] ?? $target['model'] ?? '');

            $res = $this->providers->withFallback($preferred, function ($provider) use ($messages, $request, $model, $task) {
                return $provider->chat($messages, [
                    'model' => $model !== '' ? $model : null,
                    'task_type' => 'draft_sow',
                    'client_id' => $request->client_id,
                    'user_id' => $request->created_by,
                    'timeout' => 120,
                    'task_id' => $task->id,
                ]);
            }, 'draft_sow');

            $sections = $this->parseJsonFromText((string) ($res['text'] ?? ''));
            $sections = array_map(fn ($v) => is_string($v) ? trim($v) : '', $sections);

            $task->update([
                'output_data' => $sections,
                'status' => 'completed',
                'completed_at' => now(),
                'provider_used' => $res['provider'] ?? null,
                'model_used' => $res['model'] ?? null,
                'tokens_used' => $res['tokens']['total'] ?? null,
                'cost' => $res['estimated_cost'] ?? null,
            ]);

            return $sections;
        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'output_data' => ['error' => $e->getMessage()],
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a branded SOW PDF and create a Contract in pending_signature status.
     *
     * @param  array<string,mixed>  $estimateData
     * @param  array<string,mixed>  $pricingData
     * @param  array<string,string>  $sections
     */
    public function generateSowContract(ServiceRequest $request, RequestEstimate $estimate, array $estimateData, array $pricingData, array $sections = []): Contract
    {
        $request->loadMissing(['client']);
        $client = $request->client;
        if (! $client) {
            throw new \RuntimeException('Cannot generate SOW: request has no client.');
        }

        $title = 'Statement of Work — '.$request->title;

        $midTotal = (float) (($pricingData['totals']['mid']['total'] ?? null) ?: ($pricingData['cost_range']['mid'] ?? 0));

        $contract = Contract::create([
            'client_id' => $client->id,
            'title' => $title,
            'description' => 'SOW generated from project estimate for Request #'.$request->id,
            'value' => $midTotal,
            'status' => 'pending_signature',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
        ]);

        $pdf = Pdf::loadView('documents.sow-template', [
            'appName' => config('app.name'),
            'request' => $request,
            'client' => $client,
            'estimate' => $estimateData,
            'pricing' => $pricingData,
            'sections' => $sections,
            'generatedAt' => now(),
            'contract' => $contract,
        ]);

        $bytes = $pdf->output();
        $path = 'sows/'.$contract->contract_number.'-'.Str::slug($request->title).'.pdf';
        Storage::disk('contracts')->put($path, $bytes);

        $contract->update(['file_path' => $path]);

        $estimate->update([
            'sow_contract_id' => $contract->id,
        ]);

        return $contract;
    }

    /**
     * @return array<string, mixed>
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

        Log::warning('AI SOW returned non-JSON output.');

        return [];
    }
}
