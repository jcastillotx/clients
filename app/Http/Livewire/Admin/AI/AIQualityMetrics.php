<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\AiTask;
use App\Models\AiUsageTracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AIQualityMetrics extends Component
{
    public function render()
    {
        $this->authorizeAdmin();

        $since = now()->subDays(30);

        $avgRating = (float) (AiTask::query()->where('created_at', '>=', $since)->avg('quality_rating') ?? 0.0);
        $ratedCount = (int) AiTask::query()->where('created_at', '>=', $since)->whereNotNull('quality_rating')->count();

        $byTask = AiTask::query()
            ->select('task_type', DB::raw('AVG(quality_rating) as avg_rating'), DB::raw('COUNT(*) as cnt'))
            ->where('created_at', '>=', $since)
            ->whereNotNull('quality_rating')
            ->groupBy('task_type')
            ->orderByDesc('avg_rating')
            ->get();

        $byProvider = AiUsageTracking::query()
            ->select('provider', DB::raw('AVG(response_time_ms) as avg_ms'), DB::raw('SUM(cost) as cost'))
            ->where('created_at', '>=', $since)
            ->groupBy('provider')
            ->orderByDesc('cost')
            ->get();

        $trend = AiTask::query()
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('AVG(quality_rating) as avg_rating'))
            ->where('created_at', '>=', $since)
            ->whereNotNull('quality_rating')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        return view('livewire.admin.ai.quality-metrics', [
            'avgRating' => $avgRating,
            'ratedCount' => $ratedCount,
            'byTask' => $byTask,
            'byProvider' => $byProvider,
            'trend' => $trend,
        ])->layout('layouts.admin', ['title' => 'AI Quality']);
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->can('access admin panel')) {
            abort(403);
        }
    }
}
