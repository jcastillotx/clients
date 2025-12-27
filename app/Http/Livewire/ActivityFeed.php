<?php

namespace App\Http\Livewire;

use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityFeed extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /**
     * all|requests|invoices|contracts
     */
    public string $type = 'all';

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $clientId = $user?->client_id;

        if (! $clientId) {
            return view('livewire.activity-feed', [
                'activities' => collect(),
            ]);
        }

        $perPage = 20;
        $page = (int) ($this->getPage() ?? 1);

        $cacheKey = "activity_feed:client:{$clientId}:type:{$this->type}:page:{$page}:v1";

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($clientId, $perPage, $page) {
            $base = ActivityLog::query()
                ->where('client_id', $clientId)
                ->latest();

            if ($this->type !== 'all') {
                $base->where('log_name', $this->type);
            }

            $total = (int) (clone $base)->count();
            $ids = (clone $base)
                ->forPage($page, $perPage)
                ->pluck('id')
                ->all();

            return ['total' => $total, 'ids' => $ids];
        });

        $ids = (array) ($payload['ids'] ?? []);
        $total = (int) ($payload['total'] ?? 0);

        $items = $ids
            ? ActivityLog::query()
                ->whereIn('id', $ids)
                ->with(['causer', 'subject', 'user'])
                ->get()
                ->sortBy(fn ($a) => array_search($a->id, $ids, true))
                ->values()
            : collect();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.activity-feed', [
            'activities' => $paginator,
        ]);
    }
}
