<?php

namespace App\Http\Livewire\Admin\StaffGuides;

use App\Models\StaffGuide;
use App\Models\StaffGuideCategory;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class StaffGuideManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public bool $showModal = false;

    public bool $showCategoryModal = false;

    public ?int $editingGuideId = null;

    public ?int $editingCategoryId = null;

    // Guide form fields
    public int $category_id = 0;

    public string $title = '';

    public string $slug = '';

    public string $summary = '';

    public string $content = '';

    public string $checklistJson = '';

    public string $service_tier = '';

    public ?string $price = null;

    public string $commitment = '';

    public bool $is_published = true;

    // Category form fields
    public string $cat_name = '';

    public string $cat_slug = '';

    public string $cat_icon = 'fas fa-book';

    public string $cat_description = '';

    public int $cat_sort_order = 0;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected function rules(): array
    {
        $guideSlugRule = $this->editingGuideId
            ? 'required|string|max:255|unique:staff_guides,slug,' . $this->editingGuideId
            : 'required|string|max:255|unique:staff_guides,slug';

        return [
            'category_id' => 'required|exists:staff_guide_categories,id',
            'title' => 'required|string|max:255',
            'slug' => $guideSlugRule,
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'checklistJson' => 'nullable|string',
            'service_tier' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'commitment' => 'nullable|string|max:100',
            'is_published' => 'boolean',
        ];
    }

    protected function categoryRules(): array
    {
        $catSlugRule = $this->editingCategoryId
            ? 'required|string|max:255|unique:staff_guide_categories,slug,' . $this->editingCategoryId
            : 'required|string|max:255|unique:staff_guide_categories,slug';

        return [
            'cat_name' => 'required|string|max:255',
            'cat_slug' => $catSlugRule,
            'cat_icon' => 'required|string|max:50',
            'cat_description' => 'nullable|string|max:500',
            'cat_sort_order' => 'integer|min:0',
        ];
    }

    public function updatedTitle(): void
    {
        if (! $this->editingGuideId) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function updatedCatName(): void
    {
        if (! $this->editingCategoryId) {
            $this->cat_slug = Str::slug($this->cat_name);
        }
    }

    public function openCreateModal(): void
    {
        $this->resetGuideForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $guide = StaffGuide::findOrFail($id);
        $this->editingGuideId = $guide->id;
        $this->category_id = $guide->category_id;
        $this->title = $guide->title;
        $this->slug = $guide->slug;
        $this->summary = $guide->summary ?? '';
        $this->content = $guide->content;
        $this->checklistJson = $guide->checklist ? json_encode($guide->checklist, JSON_PRETTY_PRINT) : '';
        $this->service_tier = $guide->service_tier ?? '';
        $this->price = $guide->price;
        $this->commitment = $guide->commitment ?? '';
        $this->is_published = $guide->is_published;
        $this->showModal = true;
    }

    public function saveGuide(): void
    {
        $this->validate($this->rules());

        $checklist = null;
        if ($this->checklistJson) {
            $decoded = json_decode($this->checklistJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $checklist = $decoded;
            }
        }

        $data = [
            'category_id' => $this->category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary ?: null,
            'content' => $this->content,
            'checklist' => $checklist,
            'service_tier' => $this->service_tier ?: null,
            'price' => $this->price ?: null,
            'commitment' => $this->commitment ?: null,
            'is_published' => $this->is_published,
            'author_id' => auth()->id(),
        ];

        if ($this->is_published && ! $this->editingGuideId) {
            $data['published_at'] = now();
        }

        if ($this->editingGuideId) {
            StaffGuide::where('id', $this->editingGuideId)->update($data);
            session()->flash('success', 'Guide updated successfully.');
        } else {
            StaffGuide::create($data);
            session()->flash('success', 'Guide created successfully.');
        }

        $this->closeModal();
    }

    public function deleteGuide(int $id): void
    {
        StaffGuide::where('id', $id)->delete();
        session()->flash('success', 'Guide deleted successfully.');
    }

    public function openCategoryModal(): void
    {
        $this->resetCategoryForm();
        $this->showCategoryModal = true;
    }

    public function openEditCategoryModal(int $id): void
    {
        $category = StaffGuideCategory::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->cat_name = $category->name;
        $this->cat_slug = $category->slug;
        $this->cat_icon = $category->icon;
        $this->cat_description = $category->description ?? '';
        $this->cat_sort_order = $category->sort_order;
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate($this->categoryRules());

        $data = [
            'name' => $this->cat_name,
            'slug' => $this->cat_slug,
            'icon' => $this->cat_icon,
            'description' => $this->cat_description ?: null,
            'sort_order' => $this->cat_sort_order,
        ];

        if ($this->editingCategoryId) {
            StaffGuideCategory::where('id', $this->editingCategoryId)->update($data);
            session()->flash('success', 'Category updated successfully.');
        } else {
            StaffGuideCategory::create($data);
            session()->flash('success', 'Category created successfully.');
        }

        $this->closeCategoryModal();
    }

    public function deleteCategory(int $id): void
    {
        $category = StaffGuideCategory::findOrFail($id);
        if ($category->guides()->count() > 0) {
            session()->flash('error', 'Cannot delete category with existing guides.');
            return;
        }

        $category->delete();
        session()->flash('success', 'Category deleted successfully.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetGuideForm();
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    private function resetGuideForm(): void
    {
        $this->editingGuideId = null;
        $this->category_id = 0;
        $this->title = '';
        $this->slug = '';
        $this->summary = '';
        $this->content = '';
        $this->checklistJson = '';
        $this->service_tier = '';
        $this->price = null;
        $this->commitment = '';
        $this->is_published = true;
    }

    private function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->cat_name = '';
        $this->cat_slug = '';
        $this->cat_icon = 'fas fa-book';
        $this->cat_description = '';
        $this->cat_sort_order = 0;
    }

    public function render()
    {
        $query = StaffGuide::query()->with(['category', 'author']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('summary', 'like', '%' . $this->search . '%');
            });
        }

        $guides = $query->orderBy('title')->paginate(15);

        $categories = StaffGuideCategory::orderBy('sort_order')->get();

        $serviceTiers = [
            '' => 'None',
            'local_seo' => 'Local SEO Foundation',
            'growth_seo' => 'Growth SEO',
            'authority_seo' => 'Authority SEO',
            'onboarding' => 'SEO Onboarding',
            'add_on' => 'Add-On Services',
        ];

        return view('livewire.admin.staff-guides.manager', [
            'guides' => $guides,
            'categories' => $categories,
            'serviceTiers' => $serviceTiers,
        ])->layout('layouts.admin');
    }
}
