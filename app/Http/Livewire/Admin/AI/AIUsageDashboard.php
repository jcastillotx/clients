<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\AiTask;
use App\Models\AiUsageTracking;
use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AIUsageDashboard extends Component
{
    public function render()
    {
        $this->authorizeAdmin();

        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();

        $monthSpend = (float) AiUsageTracking::query()->where('created_at', '>=', $monthStart)->sum('cost');
        $yearSpend = (float) AiUsageTracking::query()->where('created_at', '>=', $yearStart)->sum('cost');

        $byProvider = AiUsageTracking::query()
            ->select('provider', DB::raw('SUM(cost) as cost'), DB::raw('SUM(tokens_input) as ti'), DB::raw('SUM(tokens_output) as to'), DB::raw('AVG(response_time_ms) as avg_ms'))
            ->where('created_at', '>=', $monthStart)
            ->groupBy('provider')
            ->orderByDesc('cost')
            ->get()
            ->map(fn ($r) => [
                'provider' => (string) $r->provider,
                'cost' => (float) $r->cost,
                'tokens_input' => (int) $r->ti,
                'tokens_output' => (int) $r->to,
                'avg_ms' => $r->avg_ms !== null ? (int) $r->avg_ms : null,
            ]);

        $byTask = AiUsageTracking::query()
            ->select(DB::raw("COALESCE(task_type,'(unknown)') as task_type"), DB::raw('SUM(cost) as cost'))
            ->where('created_at', '>=', $monthStart)
            ->groupBy('task_type')
            ->orderByDesc('cost')
            ->get()
            ->map(fn ($r) => ['task_type' => (string) $r->task_type, 'cost' => (float) $r->cost]);

        $byClient = AiUsageTracking::query()
            ->select('client_id', DB::raw('SUM(cost) as cost'))
            ->whereNotNull('client_id')
            ->where('created_at', '>=', $monthStart)
            ->groupBy('client_id')
            ->orderByDesc('cost')
            ->limit(15)
            ->get();
        $clients = Client::query()->whereIn('id', $byClient->pluck('client_id'))->get()->keyBy('id');
        $byClient = $byClient->map(fn ($r) => [
            'client_id' => (int) $r->client_id,
            'client' => $clients[(int) $r->client_id]->company_name ?? ('Client #'.(int) $r->client_id),
            'cost' => (float) $r->cost,
        ]);

        $byUser = AiUsageTracking::query()
            ->select('user_id', DB::raw('SUM(cost) as cost'))
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $monthStart)
            ->groupBy('user_id')
            ->orderByDesc('cost')
            ->limit(15)
            ->get();
        $users = User::query()->whereIn('id', $byUser->pluck('user_id'))->get()->keyBy('id');
        $byUser = $byUser->map(fn ($r) => [
            'user_id' => (int) $r->user_id,
            'user' => $users[(int) $r->user_id]->name ?? ('User #'.(int) $r->user_id),
            'cost' => (float) $r->cost,
        ]);

        $mostExpensive = AiTask::query()
            ->whereNotNull('cost')
            ->orderByDesc('cost')
            ->limit(15)
            ->get(['id', 'task_type', 'provider_used', 'model_used', 'cost', 'created_at', 'status']);

        $trend = AiUsageTracking::query()
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(cost) as cost'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn ($r) => ['date' => (string) $r->d, 'cost' => (float) $r->cost]);

        $budget = (float) (Setting::getValue('ai.budget.monthly_limit_usd', 0.0) ?? 0.0);
        $alertPct = (float) (Setting::getValue('ai.budget.alert_pct', 0.8) ?? 0.8);
        $budgetPct = $budget > 0 ? ($monthSpend / $budget) : null;

        return view('livewire.admin.ai.usage-dashboard', [
            'monthSpend' => $monthSpend,
            'yearSpend' => $yearSpend,
            'byProvider' => $byProvider,
            'byTask' => $byTask,
            'byClient' => $byClient,
            'byUser' => $byUser,
            'mostExpensive' => $mostExpensive,
            'trend' => $trend,
            'budget' => $budget,
            'alertPct' => $alertPct,
            'budgetPct' => $budgetPct,
        ])->layout('layouts.admin', ['title' => 'AI Usage']);
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->can('access admin panel')) {
            abort(403);
        }
    }
}
