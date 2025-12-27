<?php

namespace App\Services\AI;

use App\Models\AiTask;
use App\Models\Client;
use App\Models\Setting;
use App\Services\Estimates\CostCalculationService;
use App\Services\Estimates\WorkloadCapacityService;
use Illuminate\Support\Facades\Log;

class SmartEstimationService
{
    public function __construct(
        protected AIProviderManager $providers,
        protected CostCalculationService $costs,
        protected WorkloadCapacityService $workload
    ) {}

    /**
     * Generate a smart estimate based on project description, services, rates, and workload
     *
     * @param array $projectDetails {
     *   title: string,
     *   description: string,
     *   type: string,
     *   priority: string,
     *   client_id?: int
     * }
     * @return array
     */
    public function generateEstimate(array $projectDetails, array $options = []): array
    {
        $client = isset($projectDetails['client_id'])
            ? Client::find($projectDetails['client_id'])
            : null;

        // Get current workload
        $currentWorkload = $this->workload->getCurrentWorkload();

        // Get base rate and services catalog
        $baseRate = $this->costs->defaultBaseRate();
        $servicesCatalog = $this->getServicesCatalog();

        // Create AI task for tracking
        $task = AiTask::create([
            'task_type' => 'smart_estimate',
            'input_data' => [
                'project' => $projectDetails,
                'workload_snapshot' => [
                    'utilization_pct' => $currentWorkload['utilization_pct'],
                    'available_hours_week' => $currentWorkload['available_hours_week'],
                    'backlog_hours' => $currentWorkload['estimated_backlog_hours'],
                ],
            ],
            'status' => 'processing',
            'executed_by' => $options['executed_by'] ?? null,
        ]);

        try {
            $systemPrompt = $this->buildSystemPrompt($servicesCatalog, $baseRate);
            $userPrompt = $this->buildUserPrompt($projectDetails, $currentWorkload, $client);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ];

            $target = $this->providers->routeToOptimalProvider('smart_estimate', 'high');
            $preferred = $options['provider'] ?? $target['provider'] ?? 'openai';
            $model = $options['model'] ?? $target['model'] ?? '';

            $response = $this->providers->withFallback($preferred, function ($provider) use ($messages, $task, $model, $client) {
                return $provider->chat($messages, [
                    'model' => $model !== '' ? $model : null,
                    'task_type' => 'smart_estimate',
                    'client_id' => $client?->id,
                    'timeout' => 90,
                    'task_id' => $task->id,
                ]);
            }, 'smart_estimate');

            $aiEstimate = $this->parseJsonResponse($response['text'] ?? '');

            // Calculate costs for each service line
            $serviceLines = $this->calculateServiceCosts($aiEstimate['services'] ?? [], $baseRate, $client);

            // Calculate totals
            $totals = $this->calculateTotals($serviceLines, $client);

            // Get delivery timeline based on workload
            $estimatedHours = $totals['hours']['mid'];
            $timeline = $this->workload->estimateDeliveryTimeline(
                $estimatedHours,
                $projectDetails['priority'] ?? 'medium'
            );

            $result = [
                'project_summary' => $aiEstimate['project_summary'] ?? $projectDetails['title'],
                'services' => $serviceLines,
                'totals' => $totals,
                'timeline' => $timeline,
                'assumptions' => $aiEstimate['assumptions'] ?? [],
                'out_of_scope' => $aiEstimate['out_of_scope'] ?? [],
                'risks' => $aiEstimate['risks'] ?? [],
                'recommendations' => $aiEstimate['recommendations'] ?? [],
                'workload_context' => [
                    'team_utilization' => $currentWorkload['utilization_pct'] . '%',
                    'available_capacity' => $currentWorkload['available_hours_week'] . ' hrs/week',
                    'current_backlog' => $currentWorkload['estimated_backlog_hours'] . ' hrs',
                    'open_requests' => $currentWorkload['open_requests'],
                ],
                '_meta' => [
                    'task_id' => $task->id,
                    'provider' => $response['provider'] ?? null,
                    'model' => $response['model'] ?? null,
                    'base_rate' => $baseRate,
                    'generated_at' => now()->toIso8601String(),
                ],
            ];

            $task->update([
                'output_data' => $result,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Smart estimation failed', [
                'error' => $e->getMessage(),
                'project' => $projectDetails['title'] ?? 'Unknown',
            ]);

            $task->update([
                'status' => 'failed',
                'output_data' => ['error' => $e->getMessage()],
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Get quick estimate without full AI analysis
     */
    public function getQuickEstimate(string $projectType, string $complexity = 'medium'): array
    {
        $baseRate = $this->costs->defaultBaseRate();
        $workload = $this->workload->getCurrentWorkload();

        // Standard hour ranges by project type and complexity
        $hourRanges = $this->getStandardHourRanges();
        $typeKey = strtolower($projectType);
        $complexityKey = strtolower($complexity);

        $hours = $hourRanges[$typeKey][$complexityKey] ?? $hourRanges['general'][$complexityKey] ?? [8, 16, 24];

        $timeline = $this->workload->estimateDeliveryTimeline($hours[1], 'medium');

        return [
            'hours' => [
                'low' => $hours[0],
                'mid' => $hours[1],
                'high' => $hours[2],
            ],
            'cost' => [
                'low' => number_format($hours[0] * $baseRate, 2),
                'mid' => number_format($hours[1] * $baseRate, 2),
                'high' => number_format($hours[2] * $baseRate, 2),
            ],
            'timeline' => $timeline,
            'base_rate' => $baseRate,
            'note' => 'Quick estimate based on standard project templates. Request detailed estimate for accuracy.',
        ];
    }

    protected function buildSystemPrompt(array $servicesCatalog, float $baseRate): string
    {
        $servicesJson = json_encode($servicesCatalog, JSON_PRETTY_PRINT);

        return <<<SYSTEM
You are an expert project estimator for a digital agency. Your role is to analyze project requirements and provide accurate time and cost estimates.

## Available Services & Rates
{$servicesJson}

Base hourly rate: \${$baseRate}/hour

## Your Task
Analyze the project description and provide a detailed estimate with:
1. Break down into specific service line items
2. Provide low/mid/high hour estimates for each
3. Identify assumptions and out-of-scope items
4. Flag potential risks
5. Provide recommendations

## Response Format (JSON)
{
    "project_summary": "Brief summary of what the project entails",
    "services": [
        {
            "name": "Service name from catalog or custom",
            "description": "What this covers",
            "hours_low": 4,
            "hours_mid": 6,
            "hours_high": 10,
            "phase": "discovery|design|development|testing|deployment",
            "optional": false
        }
    ],
    "assumptions": ["List of assumptions made"],
    "out_of_scope": ["Items explicitly not included"],
    "risks": ["Potential risks that could affect timeline/cost"],
    "recommendations": ["Suggestions for the client"]
}

Be thorough but realistic. Consider typical project complexities and common scope creep areas.
SYSTEM;
    }

    protected function buildUserPrompt(array $project, array $workload, ?Client $client): string
    {
        $clientInfo = $client
            ? "Client: {$client->company_name} (Tier: {$client->tier})"
            : "New client inquiry";

        $workloadContext = <<<WORKLOAD
Current Team Workload:
- Team utilization: {$workload['utilization_pct']}%
- Available capacity: {$workload['available_hours_week']} hours/week
- Current backlog: {$workload['estimated_backlog_hours']} hours
- Open requests in queue: {$workload['open_requests']}
WORKLOAD;

        $projectJson = json_encode($project, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<USER
Please estimate this project:

{$clientInfo}

Project Details:
{$projectJson}

{$workloadContext}

Consider the current workload when making timeline recommendations. Provide a comprehensive estimate.
USER;
    }

    protected function getServicesCatalog(): array
    {
        $catalog = Setting::getValue('billing.services_catalog', null);

        if (is_array($catalog) && !empty($catalog)) {
            return $catalog;
        }

        $rate = $this->costs->defaultBaseRate();

        return [
            ['name' => 'Discovery & Planning', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Planning'],
            ['name' => 'UI/UX Design', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Design'],
            ['name' => 'Web Development', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Development'],
            ['name' => 'Backend Development', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Development'],
            ['name' => 'API Integration', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Development'],
            ['name' => 'Testing & QA', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Quality'],
            ['name' => 'Project Management', 'unit' => 'hour', 'default_rate' => $rate * 0.8, 'category' => 'Management'],
            ['name' => 'Content Creation', 'unit' => 'hour', 'default_rate' => $rate * 0.75, 'category' => 'Content'],
            ['name' => 'SEO Setup', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Marketing'],
            ['name' => 'Training & Documentation', 'unit' => 'hour', 'default_rate' => $rate * 0.75, 'category' => 'Support'],
            ['name' => 'Deployment & Launch', 'unit' => 'hour', 'default_rate' => $rate, 'category' => 'Deployment'],
            ['name' => 'Maintenance & Support', 'unit' => 'hour', 'default_rate' => $rate * 0.9, 'category' => 'Support'],
        ];
    }

    protected function getStandardHourRanges(): array
    {
        return [
            'website' => [
                'simple' => [20, 40, 60],
                'medium' => [60, 100, 150],
                'complex' => [150, 250, 400],
            ],
            'web_application' => [
                'simple' => [80, 120, 180],
                'medium' => [200, 350, 500],
                'complex' => [500, 800, 1200],
            ],
            'mobile_app' => [
                'simple' => [100, 160, 240],
                'medium' => [300, 500, 750],
                'complex' => [600, 1000, 1500],
            ],
            'ecommerce' => [
                'simple' => [60, 100, 150],
                'medium' => [150, 250, 400],
                'complex' => [400, 650, 1000],
            ],
            'branding' => [
                'simple' => [15, 25, 40],
                'medium' => [40, 70, 100],
                'complex' => [100, 160, 250],
            ],
            'marketing' => [
                'simple' => [10, 20, 30],
                'medium' => [30, 50, 80],
                'complex' => [80, 130, 200],
            ],
            'support' => [
                'simple' => [2, 4, 8],
                'medium' => [8, 16, 24],
                'complex' => [24, 40, 60],
            ],
            'general' => [
                'simple' => [8, 16, 24],
                'medium' => [24, 48, 80],
                'complex' => [80, 150, 250],
            ],
        ];
    }

    protected function calculateServiceCosts(array $services, float $baseRate, ?Client $client): array
    {
        $result = [];

        foreach ($services as $service) {
            if (!is_array($service) || empty($service['name'])) {
                continue;
            }

            $hoursLow = (float) ($service['hours_low'] ?? 0);
            $hoursMid = (float) ($service['hours_mid'] ?? 0);
            $hoursHigh = (float) ($service['hours_high'] ?? 0);

            // Ensure proper ordering
            if ($hoursMid > 0 && $hoursLow <= 0) {
                $hoursLow = $hoursMid * 0.7;
            }
            if ($hoursMid > 0 && $hoursHigh <= 0) {
                $hoursHigh = $hoursMid * 1.4;
            }

            $result[] = [
                'name' => $service['name'],
                'description' => $service['description'] ?? '',
                'phase' => $service['phase'] ?? 'development',
                'optional' => (bool) ($service['optional'] ?? false),
                'hours' => [
                    'low' => round($hoursLow, 1),
                    'mid' => round($hoursMid, 1),
                    'high' => round($hoursHigh, 1),
                ],
                'cost' => [
                    'low' => round($hoursLow * $baseRate, 2),
                    'mid' => round($hoursMid * $baseRate, 2),
                    'high' => round($hoursHigh * $baseRate, 2),
                ],
            ];
        }

        return $result;
    }

    protected function calculateTotals(array $serviceLines, ?Client $client): array
    {
        $hours = ['low' => 0, 'mid' => 0, 'high' => 0];

        foreach ($serviceLines as $line) {
            if ($line['optional'] ?? false) {
                continue; // Don't include optional items in totals
            }
            $hours['low'] += $line['hours']['low'] ?? 0;
            $hours['mid'] += $line['hours']['mid'] ?? 0;
            $hours['high'] += $line['hours']['high'] ?? 0;
        }

        $baseRate = $this->costs->defaultBaseRate();
        $markupPct = $this->costs->defaultMarkupPct();

        // Determine complexity-based contingency
        $avgHours = $hours['mid'];
        $complexity = match (true) {
            $avgHours > 200 => 8,
            $avgHours > 100 => 6,
            $avgHours > 50 => 5,
            default => 3,
        };
        $contingencyPct = $this->costs->contingencyPctForComplexity($complexity);

        return [
            'hours' => $hours,
            'subtotal' => [
                'low' => round($hours['low'] * $baseRate, 2),
                'mid' => round($hours['mid'] * $baseRate, 2),
                'high' => round($hours['high'] * $baseRate, 2),
            ],
            'with_markup' => [
                'low' => $this->costs->calculate($hours['low'], $baseRate, $client, $markupPct, $contingencyPct),
                'mid' => $this->costs->calculate($hours['mid'], $baseRate, $client, $markupPct, $contingencyPct),
                'high' => $this->costs->calculate($hours['high'], $baseRate, $client, $markupPct, $contingencyPct),
            ],
            'base_rate' => $baseRate,
            'markup_pct' => $markupPct,
            'contingency_pct' => $contingencyPct,
        ];
    }

    protected function parseJsonResponse(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Try direct parse
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try to find JSON object
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('Smart estimation returned non-JSON output', ['text' => substr($text, 0, 500)]);

        return [];
    }
}
