<?php

namespace App\Http\Livewire\Client;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use App\Models\StorageConnection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()?->isClient(), 403);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        $clientId = (int) $user->client_id;

        $spendByMonth = Payment::query()
            ->selectRaw("DATE_FORMAT(processed_at, '%Y-%m') as ym, SUM(amount) as total")
            ->where('client_id', $clientId)
            ->whereNotNull('processed_at')
            ->groupBy('ym')
            ->orderBy('ym')
            ->limit(12)
            ->get();

        $requestTotal = ServiceRequest::query()->where('client_id', $clientId)->count();
        $requestCompleted = ServiceRequest::query()->where('client_id', $clientId)->where('status', 'completed')->count();
        $completionRate = $requestTotal > 0 ? (int) floor(($requestCompleted / $requestTotal) * 100) : 0;

        $avgResponseHours = DB::table('request_comments')
            ->join('requests', 'requests.id', '=', 'request_comments.request_id')
            ->where('requests.client_id', $clientId)
            ->where('request_comments.is_internal', false)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, requests.created_at, request_comments.created_at)) as avg_hours')
            ->value('avg_hours');

        
        $storage = StorageConnection::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->get()
            ->map(fn ($c) => [
                'provider' => $c->provider,
                'name' => $c->name,
                'used_bytes' => (int) $c->used_bytes,
                'quota_bytes' => (int) $c->quota_bytes,
            ]);

        $unpaid = Invoice::query()->where('client_id', $clientId)->whereIn('status', ['sent', 'overdue'])->sum('amount');

        return view('livewire.client.analytics-dashboard', compact(
            'spendByMonth',
            'completionRate',
            'avgResponseHours',
            'storage',
            'unpaid'
        ))->layout('layouts.app', ['title' => 'Client Analytics']);
    }
}
