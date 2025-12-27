<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\AiReviewQueueItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AIReviewQueue extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $status = 'pending';

    public string $category = '';

    /** @var array<int,string> */
    public array $approvedText = [];

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $this->authorizeAdmin();

        $item = AiReviewQueueItem::query()->findOrFail($id);
        $text = trim((string) ($this->approvedText[$id] ?? $item->output_preview ?? ''));

        $item->update([
            'status' => 'approved',
            'approved_text' => $text !== '' ? $text : null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        session()->flash('success', 'Approved.');
    }

    public function reject(int $id): void
    {
        $this->authorizeAdmin();
        AiReviewQueueItem::query()->whereKey($id)->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        session()->flash('success', 'Rejected.');
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->can('access admin panel')) {
            abort(403);
        }
    }

    public function render()
    {
        $this->authorizeAdmin();

        $q = AiReviewQueueItem::query()->orderByDesc('id');
        if ($this->status !== '') {
            $q->where('status', $this->status);
        }
        if ($this->category !== '') {
            $q->where('category', $this->category);
        }

        $items = $q->paginate(20);
        foreach ($items as $i) {
            $this->approvedText[$i->id] = $this->approvedText[$i->id] ?? ($i->approved_text ?? $i->output_preview ?? '');
        }

        $categories = AiReviewQueueItem::query()->distinct()->pluck('category')->all();
        sort($categories);

        return view('livewire.admin.ai.review-queue', [
            'items' => $items,
            'categories' => $categories,
        ])->layout('layouts.admin', ['title' => 'AI Review Queue']);
    }
}
