<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the client dashboard.
     */
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
        $client = $user->client;

        // If user doesn't have a client, redirect to admin dashboard
        if (!$client) {
            return redirect()->route('admin.dashboard');
        }

        // Get dashboard statistics
        $stats = [
            'open_requests' => Request::where('client_id', $client->id)
                ->open()
                ->count(),
            'pending_invoices' => Invoice::where('client_id', $client->id)
                ->unpaid()
                ->count(),
            'active_contracts' => Contract::where('client_id', $client->id)
                ->active()
                ->count(),
            'total_due' => Invoice::where('client_id', $client->id)
                ->unpaid()
                ->sum('amount'),
        ];

        // Get recent requests
        $recentRequests = Request::where('client_id', $client->id)
            ->with(['assignee'])
            ->latest()
            ->take(5)
            ->get();

        // Get recent invoices
        $recentInvoices = Invoice::where('client_id', $client->id)
            ->latest()
            ->take(5)
            ->get();

        // Get contracts expiring soon
        $expiringContracts = Contract::where('client_id', $client->id)
            ->expiringSoon(30)
            ->get();

        // Get pending signature contracts
        $pendingContracts = Contract::where('client_id', $client->id)
            ->pendingSignature()
            ->get();

        return view('dashboard', compact(
            'stats',
            'recentRequests',
            'recentInvoices',
            'expiringContracts',
            'pendingContracts'
        ));
    }
}
