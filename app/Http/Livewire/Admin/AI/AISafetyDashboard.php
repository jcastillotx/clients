<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\AiComplianceLog;
use App\Models\AiReviewQueueItem;
use App\Models\AiUsageTracking;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AISafetyDashboard extends Component
{
    public function render()
    {
        $this->authorizeAdmin();

        $last7 = now()->subDays(7);
        $pendingReviews = AiReviewQueueItem::query()->where('status', 'pending')->count();
        $pii7 = AiComplianceLog::query()->where('created_at', '>=', $last7)->where('pii_detected', true)->count();
        $flagged7 = AiComplianceLog::query()->where('created_at', '>=', $last7)->where('flagged_for_review', true)->count();

        $byFlag = AiComplianceLog::query()
            ->where('created_at', '>=', $last7)
            ->select('flags', DB::raw('count(*) as cnt'))
            ->groupBy('flags')
            ->orderByDesc('cnt')
            ->limit(20)
            ->get();

        $budget = (float) (Setting::getValue('ai.budget.monthly_limit_usd', 0.0) ?? 0.0);
        $monthSpend = (float) AiUsageTracking::query()->where('created_at', '>=', now()->startOfMonth())->sum('cost');

        return view('livewire.admin.ai.safety-dashboard', [
            'pendingReviews' => $pendingReviews,
            'pii7' => $pii7,
            'flagged7' => $flagged7,
            'byFlag' => $byFlag,
            'budget' => $budget,
            'monthSpend' => $monthSpend,
        ])->layout('layouts.admin', ['title' => 'AI Safety']);
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->can('access admin panel')) {
            abort(403);
        }
    }
}
