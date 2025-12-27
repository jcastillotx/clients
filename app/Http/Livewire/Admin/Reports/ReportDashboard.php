<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Services\AdminReports\ReportDataService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ReportDashboard extends Component
{
    public string $category = 'financial';

    public string $granularity = 'month';

    public ?string $start_date = null;

    public ?string $end_date = null;

    /** @var array{meta: array, charts: array, tables: array} */
    public array $payload = [
        'meta' => [],
        'charts' => [],
        'tables' => [],
    ];

    // Custom builder
    public string $template_name = '';

    public string $template_description = '';

    public array $template_metrics = [];

    // Scheduling
    public ?int $schedule_template_id = null;

    public string $schedule_frequency = 'weekly';

    public string $schedule_recipients = '';

    public bool $schedule_is_active = true;

    protected array $queryString = [
        'category' => ['except' => 'financial'],
        'granularity' => ['except' => 'month'],
        'start_date' => ['except' => null],
        'end_date' => ['except' => null],
    ];

    public function mount(ReportDataService $data): void
    {
        $this->category = $this->category ?: 'financial';
        $this->granularity = $this->granularity ?: 'month';
        $this->apply(false, $data);
    }

    public function apply(bool $dispatch = true, ?ReportDataService $data = null): void
    {
        $data ??= app(ReportDataService::class);

        $this->validateDates();

        $this->payload = $data->build($this->category, [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'granularity' => $this->granularity,
        ]);

        if ($dispatch) {
            $this->dispatch('reports-updated', payload: $this->payload);
        }
    }

    public function updatedCategory(): void
    {
        $this->apply();
    }

    public function updatedGranularity(): void
    {
        if ($this->category === 'financial') {
            $this->apply();
        }
    }

    public function saveTemplate(): void
    {
        $userId = Auth::id();

        Validator::make([
            'template_name' => $this->template_name,
            'template_metrics' => $this->template_metrics,
        ], [
            'template_name' => ['required', 'string', 'max:120'],
            'template_metrics' => ['required', 'array', 'min:1'],
        ])->validate();

        $template = ReportTemplate::create([
            'name' => $this->template_name,
            'description' => $this->template_description,
            'created_by' => $userId,
            'config' => [
                'category' => $this->category,
                'granularity' => $this->granularity,
                'metrics' => $this->template_metrics,
                'filters' => [
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                ],
            ],
        ]);

        $this->schedule_template_id = $template->id;
        $this->template_name = '';
        $this->template_description = '';
        $this->template_metrics = [];

        session()->flash('success', 'Report template saved.');
    }

    public function createSchedule(): void
    {
        Validator::make([
            'schedule_template_id' => $this->schedule_template_id,
            'schedule_frequency' => $this->schedule_frequency,
            'schedule_recipients' => $this->schedule_recipients,
        ], [
            'schedule_template_id' => ['required', 'integer', 'exists:report_templates,id'],
            'schedule_frequency' => ['required', 'in:daily,weekly,monthly'],
            'schedule_recipients' => ['required', 'string'],
        ])->validate();

        $emails = collect(explode(',', $this->schedule_recipients))
            ->map(fn ($e) => trim($e))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($emails as $email) {
            Validator::make(['email' => $email], ['email' => ['email']])->validate();
        }

        ReportSchedule::create([
            'report_template_id' => $this->schedule_template_id,
            'created_by' => Auth::id(),
            'frequency' => $this->schedule_frequency,
            'recipients' => $emails,
            'is_active' => $this->schedule_is_active,
            'next_run_at' => now()->addMinutes(1),
        ]);

        session()->flash('success', 'Report schedule created. (Next run is queued for soon; mail config required.)');
    }

    public function getExportQueryProperty(): array
    {
        return array_filter([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'granularity' => $this->granularity,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function getTemplatesProperty()
    {
        return ReportTemplate::query()
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    public function getSchedulesProperty()
    {
        return ReportSchedule::query()
            ->with('template')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    protected function validateDates(): void
    {
        if (! $this->start_date || ! $this->end_date) {
            return;
        }

        $v = Validator::make([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ], [
            'start_date' => ['date'],
            'end_date' => ['date', 'after_or_equal:start_date'],
        ]);

        $v->validate();
    }

    public function render()
    {
        $categories = [
            'financial' => 'Financial Reports',
            'clients' => 'Client Reports',
            'requests' => 'Request Reports',
            'performance' => 'Performance Reports',
            'storage' => 'Storage Reports',
        ];

        $metricOptions = [
            'financial' => [
                'revenueTrend' => 'Revenue trend (month/quarter/year)',
                'revenueByTier' => 'Revenue by client tier',
                'revenueByServiceType' => 'Revenue by service type',
                'paymentMethods' => 'Payment method breakdown',
                'invoiceAging' => 'Invoice aging buckets',
                'invoiceAgingByClient' => 'Invoice aging (client breakdown)',
                'plSummary' => 'Profit & Loss summary',
                'unpaidInvoices' => 'Outstanding invoices list',
            ],
            'clients' => [
                'acquisition' => 'Client acquisition (new clients over time)',
                'clientsByTier' => 'Clients by tier',
                'clientsByStatus' => 'Clients by status',
                'retention' => 'Retention (best-effort)',
                'ltv' => 'Client lifetime value (top)',
                'mostActiveByRequests' => 'Most active clients (by requests)',
                'mostActiveByRevenue' => 'Most active clients (by revenue)',
                'churnRisk' => 'Churn risk (inactive clients)',
            ],
            'requests' => [
                'byType' => 'Request volume by type',
                'byStatus' => 'Request volume by status',
                'byPriority' => 'Request volume by priority',
                'avgCompletionByType' => 'Average completion time by type',
                'staffProductivity' => 'Staff productivity',
                'bottlenecks' => 'Bottleneck analysis',
                'sla' => 'SLA compliance',
            ],
            'performance' => [
                'responseTime' => 'Average response time',
                'resolutionTime' => 'Average resolution time',
                'workload' => 'Staff workload distribution',
                'monthly' => 'Monthly performance trends',
            ],
            'storage' => [
                'usageByClient' => 'Storage usage by client',
                'fileTypes' => 'File type distribution',
                'largeFiles' => 'Large file alerts',
                'provider' => 'Storage provider summary',
                'sync' => 'Sync success rate (placeholder)',
            ],
        ];

        return view('livewire.admin.reports.dashboard', [
            'categories' => $categories,
            'metricOptions' => Arr::get($metricOptions, $this->category, []),
        ]);
    }
}
