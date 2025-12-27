<?php

namespace App\Http\Livewire\Client;

use App\Models\Request as ServiceRequest;
use App\Services\AI\SmartEstimationService;
use App\Services\Estimates\WorkloadCapacityService;
use Livewire\Component;

class EstimateRequest extends Component
{
    // Form fields
    public string $title = '';
    public string $description = '';
    public string $project_type = 'website';
    public string $complexity = 'medium';
    public string $priority = 'medium';
    public ?string $budget_range = null;
    public ?string $deadline = null;
    public array $features = [];
    public string $additional_notes = '';

    // Estimate result
    public ?array $estimate = null;
    public bool $isGenerating = false;
    public ?string $error = null;

    // Quick estimate mode
    public bool $showQuickEstimate = false;
    public ?array $quickEstimate = null;

    protected $rules = [
        'title' => 'required|string|min:5|max:255',
        'description' => 'required|string|min:20|max:5000',
        'project_type' => 'required|in:website,web_application,mobile_app,ecommerce,branding,marketing,support,other',
        'complexity' => 'required|in:simple,medium,complex',
        'priority' => 'required|in:low,medium,high,urgent',
        'budget_range' => 'nullable|string',
        'deadline' => 'nullable|date|after:today',
        'additional_notes' => 'nullable|string|max:2000',
    ];

    public function mount()
    {
        // Pre-fill with any existing draft
    }

    public function updatedProjectType()
    {
        $this->showQuickEstimate = false;
        $this->quickEstimate = null;
    }

    public function updatedComplexity()
    {
        $this->showQuickEstimate = false;
        $this->quickEstimate = null;
    }

    public function getQuickEstimate(WorkloadCapacityService $workload, SmartEstimationService $estimation)
    {
        $this->quickEstimate = $estimation->getQuickEstimate($this->project_type, $this->complexity);
        $this->showQuickEstimate = true;
    }

    public function generateEstimate(SmartEstimationService $estimation)
    {
        $this->validate();

        $this->isGenerating = true;
        $this->error = null;
        $this->estimate = null;

        try {
            $projectDetails = [
                'title' => $this->title,
                'description' => $this->description,
                'type' => $this->project_type,
                'complexity' => $this->complexity,
                'priority' => $this->priority,
                'budget_range' => $this->budget_range,
                'deadline' => $this->deadline,
                'features' => $this->features,
                'additional_notes' => $this->additional_notes,
                'client_id' => auth()->user()->client_id,
            ];

            $this->estimate = $estimation->generateEstimate($projectDetails, [
                'executed_by' => auth()->id(),
            ]);

            session()->flash('success', 'Estimate generated successfully!');
        } catch (\Exception $e) {
            $this->error = 'Failed to generate estimate. Please try again or contact support.';
            \Log::error('Estimate generation failed', ['error' => $e->getMessage()]);
        } finally {
            $this->isGenerating = false;
        }
    }

    public function submitAsRequest()
    {
        if (!$this->estimate) {
            session()->flash('error', 'Please generate an estimate first.');
            return;
        }

        $this->validate([
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:20',
        ]);

        $request = ServiceRequest::create([
            'client_id' => auth()->user()->client_id,
            'created_by' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description . "\n\n---\n\n**Additional Notes:**\n" . $this->additional_notes,
            'type' => $this->mapProjectTypeToRequestType($this->project_type),
            'priority' => $this->priority,
            'status' => 'pending',
            'estimated_hours' => $this->estimate['totals']['hours']['mid'] ?? null,
            'estimated_cost' => $this->estimate['totals']['with_markup']['mid']['total'] ?? null,
        ]);

        session()->flash('success', 'Your request has been submitted! We\'ll review it and get back to you soon.');

        return redirect()->route('requests.show', $request);
    }

    public function resetForm()
    {
        $this->reset([
            'title', 'description', 'project_type', 'complexity', 'priority',
            'budget_range', 'deadline', 'features', 'additional_notes',
            'estimate', 'error', 'quickEstimate', 'showQuickEstimate'
        ]);
        $this->project_type = 'website';
        $this->complexity = 'medium';
        $this->priority = 'medium';
    }

    protected function mapProjectTypeToRequestType(string $projectType): string
    {
        return match ($projectType) {
            'website', 'web_application', 'ecommerce' => 'development',
            'mobile_app' => 'development',
            'branding' => 'design',
            'marketing' => 'marketing',
            'support' => 'support',
            default => 'general',
        };
    }

    public function render(WorkloadCapacityService $workload)
    {
        $currentWorkload = $workload->getCurrentWorkload();

        return view('livewire.client.estimate-request', [
            'workloadSummary' => [
                'utilization' => $currentWorkload['utilization_pct'],
                'status' => $this->getWorkloadStatus($currentWorkload['utilization_pct']),
                'estimated_start' => $this->getEstimatedStartMessage($currentWorkload),
            ],
            'projectTypes' => [
                'website' => 'Website',
                'web_application' => 'Web Application',
                'mobile_app' => 'Mobile App',
                'ecommerce' => 'E-Commerce',
                'branding' => 'Branding & Design',
                'marketing' => 'Marketing Campaign',
                'support' => 'Support & Maintenance',
                'other' => 'Other',
            ],
            'complexityLevels' => [
                'simple' => 'Simple - Basic features, standard design',
                'medium' => 'Medium - Custom features, moderate complexity',
                'complex' => 'Complex - Advanced features, integrations, custom solutions',
            ],
            'budgetRanges' => [
                'under_5k' => 'Under $5,000',
                '5k_15k' => '$5,000 - $15,000',
                '15k_30k' => '$15,000 - $30,000',
                '30k_50k' => '$30,000 - $50,000',
                '50k_100k' => '$50,000 - $100,000',
                'over_100k' => 'Over $100,000',
                'not_sure' => 'Not sure / Flexible',
            ],
        ])->layout('layouts.app', ['title' => 'Get Project Estimate']);
    }

    protected function getWorkloadStatus(float $utilization): string
    {
        return match (true) {
            $utilization >= 90 => 'high',
            $utilization >= 70 => 'moderate',
            default => 'available',
        };
    }

    protected function getEstimatedStartMessage(array $workload): string
    {
        $utilization = $workload['utilization_pct'];

        if ($utilization >= 90) {
            return 'Our team is currently at high capacity. New projects typically start within 2-3 weeks.';
        } elseif ($utilization >= 70) {
            return 'We have moderate availability. New projects can typically start within 1-2 weeks.';
        } else {
            return 'We have good availability! New projects can often start within a few days.';
        }
    }
}
