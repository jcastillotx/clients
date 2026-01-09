<?php

namespace App\Http\Livewire\Admin\StaffGuides;

use App\Models\StaffGuide;
use App\Models\StaffGuideCategory;
use Livewire\Component;
use Livewire\WithPagination;

class StaffGuidesIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $categoryFilter = '';

    public string $tierFilter = '';

    public ?int $selectedGuideId = null;

    public array $categories = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'tierFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->categories = StaffGuideCategory::orderBy('sort_order')->get()->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTierFilter(): void
    {
        $this->resetPage();
    }

    public function viewGuide(int $guideId): void
    {
        $this->selectedGuideId = $guideId;

        // Record view
        $guide = StaffGuide::find($guideId);
        if ($guide) {
            $guide->recordView();
        }
    }

    public function closeGuide(): void
    {
        $this->selectedGuideId = null;
    }

    public function render()
    {
        $query = StaffGuide::query()
            ->with(['category', 'author'])
            ->published();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('summary', 'like', '%' . $this->search . '%')
                    ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->tierFilter) {
            $query->where('service_tier', $this->tierFilter);
        }

        $guides = $query->orderBy('title')->paginate(12);

        $selectedGuide = $this->selectedGuideId
            ? StaffGuide::with(['category', 'author'])->find($this->selectedGuideId)
            : null;

        $serviceTiers = [
            'local_seo' => 'Local SEO Foundation',
            'growth_seo' => 'Growth SEO',
            'authority_seo' => 'Authority SEO',
            'onboarding' => 'SEO Onboarding',
            'add_on' => 'Add-On Services',
        ];

        return view('livewire.admin.staff-guides.index', [
            'guides' => $guides,
            'selectedGuide' => $selectedGuide,
            'serviceTiers' => $serviceTiers,
        ])->layout('layouts.admin');
    }
}
