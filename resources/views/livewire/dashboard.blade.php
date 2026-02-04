<div class="space-y-6">
    <!-- Loading overlay -->
    <div
        wire:loading.flex
        class="fixed inset-0 z-50 items-center justify-center bg-slate-900/20 backdrop-blur-sm"
        aria-label="Loading"
    >
        <div class="flex items-center gap-3 rounded-xl theme-bg-card density-px-lg density-py-sm theme-shadow-lg ring-1 ring-black/5">
            <svg class="h-5 w-5 animate-spin theme-text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm font-medium theme-text-secondary">Loading dashboard...</span>
        </div>
    </div>

    <!-- Greeting -->
    <div class="mb-2">
        <h1 class="text-2xl font-semibold theme-text-primary">Hello, {{ auth()->user()->first_name ?? auth()->user()->name }}</h1>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Revenue Card -->
        <div class="group rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm transition-all duration-300 hover:theme-shadow-md">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-primary/10 transition-transform duration-300 group-hover:scale-110">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium theme-text-muted">Revenue</div>
                        <div class="flex items-center gap-2">
                            <select class="border-0 bg-transparent p-0 text-xs theme-text-muted focus:ring-0" wire:model="selectedCurrency">
                                <option value="USD">US Dollar...</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="h-10 w-16">
                    <canvas id="revenueSparkline" height="40"></canvas>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-heading theme-text-primary">@money($totalRevenue)</div>
                <div class="mt-1 flex items-center gap-1">
                    @if($revenueChange >= 0)
                        <span class="inline-flex items-center text-xs font-medium text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-0.5 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            {{ $revenueChange }}%
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs font-medium text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-0.5 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            {{ abs($revenueChange) }}%
                        </span>
                    @endif
                    <span class="text-xs theme-text-muted">vs last 30 days</span>
                </div>
            </div>
        </div>

        <!-- Orders Card -->
        <div class="group rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm transition-all duration-300 hover:theme-shadow-md">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-secondary/40 transition-transform duration-300 group-hover:scale-110">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium theme-text-muted">Active Requests</div>
                    </div>
                </div>
                <div class="h-10 w-16">
                    <canvas id="ordersSparkline" height="40"></canvas>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-heading theme-text-primary">{{ $totalOrders }}</div>
                <div class="mt-1 flex items-center gap-1">
                    @if($ordersChange >= 0)
                        <span class="inline-flex items-center text-xs font-medium text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-0.5 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            {{ $ordersChange }}%
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs font-medium text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-0.5 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            {{ abs($ordersChange) }}%
                        </span>
                    @endif
                    <span class="text-xs theme-text-muted">vs last 30 days</span>
                </div>
            </div>
        </div>

        <!-- Open Tickets Card -->
        <div class="group rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm transition-all duration-300 hover:theme-shadow-md">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 transition-transform duration-300 group-hover:scale-110">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium theme-text-muted">Open Tickets</div>
                    </div>
                </div>
                <div class="h-10 w-16">
                    <canvas id="ticketsSparkline" height="40"></canvas>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-heading theme-text-primary">{{ $openTickets }}</div>
                <div class="mt-1 flex items-center gap-1">
                    @if($ticketsChange >= 0)
                        <span class="inline-flex items-center text-xs font-medium text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-0.5 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            {{ $ticketsChange }}%
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs font-medium text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-0.5 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            {{ abs($ticketsChange) }}%
                        </span>
                    @endif
                    <span class="text-xs theme-text-muted">vs last 30 days</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Overview & Recent Tickets -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Revenue Overview -->
        <div class="rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="text-sm font-semibold font-heading theme-text-primary">Revenue Overview</div>
                </div>
                <select class="rounded-lg theme-border-primary theme-bg-card density-px-sm density-py-xs text-xs font-medium theme-text-secondary focus:theme-border-secondary focus:ring-0">
                    <option>US Dollar...</option>
                </select>
            </div>
            <div class="mt-4" style="height: 200px;">
                <canvas id="revenueOverviewChart"></canvas>
            </div>
            @if(array_sum($invoiceTrendChart['paid'] ?? []) == 0)
                <div class="flex flex-col items-center justify-center py-8">
                    <div class="mb-3 theme-text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <p class="text-sm theme-text-muted">No Revenue Overview found</p>
                </div>
            @endif
        </div>

        <!-- Recent Open Tickets -->
        <div class="rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <div class="text-sm font-semibold font-heading theme-text-primary">Recent Open Tickets</div>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentTickets as $ticket)
                    <a href="{{ route('support-tickets.show', $ticket) }}" class="block rounded-xl theme-border-muted theme-bg-card density-px-sm density-py-sm transition-colors hover:theme-bg-secondary">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium theme-text-primary">{{ $ticket->subject }}</div>
                                <div class="mt-0.5 text-xs theme-text-muted">{{ $ticket->ticket_number }} - {{ $ticket->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @if($ticket->priority === 'urgent') bg-red-100 text-red-700
                                @elseif($ticket->priority === 'high') bg-orange-100 text-orange-700
                                @elseif($ticket->priority === 'medium') bg-blue-100 text-blue-700
                                @else bg-slate-100 text-slate-700
                                @endif">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="mb-3 theme-text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                        </div>
                        <p class="text-sm theme-text-muted">No Recent Open Ticket found</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Unpaid Invoices -->
    <div class="rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                </svg>
            </div>
            <div class="text-sm font-semibold font-heading theme-text-primary">Unpaid Invoices</div>
        </div>
        <div class="mt-4">
            @forelse($upcomingInvoices as $invoice)
                <a href="{{ route('invoices.show', $invoice) }}" class="block theme-border-muted py-3 last:border-0 transition-colors hover:theme-bg-secondary" style="border-bottom: 1px solid var(--border-muted);">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold theme-text-primary">{{ $invoice->invoice_number }}</div>
                            <div class="text-xs theme-text-muted">Due {{ $invoice->due_date?->format('M d, Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold theme-text-primary">@money($invoice->amount)</div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @if($invoice->status === 'overdue') bg-red-100 text-red-700
                                @else bg-amber-100 text-amber-700
                                @endif">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center py-8">
                    <div class="mb-3 theme-text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 14l2 2 4-4M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                        </svg>
                    </div>
                    <p class="text-sm theme-text-muted">No Invoices Found</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-secondary/40">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div class="text-sm font-semibold font-heading theme-text-primary">Recent Orders</div>
        </div>
        <div class="mt-4">
            @forelse($recentOrders as $order)
                <a href="{{ route('requests.show', $order) }}" class="block theme-border-muted py-3 last:border-0 transition-colors hover:theme-bg-secondary" style="border-bottom: 1px solid var(--border-muted);">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold theme-text-primary">{{ $order->title }}</div>
                            <div class="text-xs theme-text-muted">{{ $order->created_at->format('M d, Y') }}</div>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            @if($order->status === 'completed') bg-emerald-100 text-emerald-700
                            @elseif($order->status === 'in_progress') bg-blue-100 text-blue-700
                            @elseif($order->status === 'pending') bg-amber-100 text-amber-700
                            @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                            @else bg-slate-100 text-slate-700
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center py-8">
                    <div class="mb-3 theme-text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <p class="text-sm theme-text-muted">No Recent Order found</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-base font-semibold font-heading theme-text-primary">Quick actions</div>
                <div class="mt-1 text-sm theme-text-muted">Common tasks to keep things moving.</div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('requests.create') }}" class="inline-flex items-center rounded-lg bg-brand-primary px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-primary/90">
                    New Request
                </a>
                <a href="{{ route('invoices.index') }}" class="inline-flex items-center rounded-lg theme-border-secondary theme-bg-card px-3 py-2 text-sm font-semibold theme-text-primary transition-colors hover:theme-bg-secondary">
                    Pay Invoice
                </a>
                <a href="{{ route('contracts.index') }}" class="inline-flex items-center rounded-lg theme-border-secondary theme-bg-card px-3 py-2 text-sm font-semibold theme-text-primary transition-colors hover:theme-bg-secondary">
                    View Contracts
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Contract Expirations -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Activity -->
        <div class="rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold font-heading theme-text-primary">Recent activity</h2>
                <span class="text-xs font-medium theme-text-muted">Last 10</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentActivity as $item)
                    <div class="flex items-start gap-3 rounded-xl theme-border-muted theme-bg-secondary density-px-sm density-py-sm">
                        <div class="mt-0.5 h-2.5 w-2.5 rounded-full bg-brand-primary/60"></div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium theme-text-primary">
                                {{ $item->description }}
                            </div>
                            <div class="mt-0.5 text-xs theme-text-muted">
                                {{ $item->created_at?->diffForHumans() }}
                                @if($item->user)
                                    - {{ $item->user->name }}
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed theme-border-primary density-p-lg text-center text-sm theme-text-muted">
                        No recent activity yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Contract Expirations -->
        <div class="rounded-2xl theme-border-primary theme-bg-card density-p-lg theme-shadow-sm">
            <h2 class="text-base font-semibold font-heading theme-text-primary">Contract expirations</h2>
            <div class="mt-4 space-y-3">
                @forelse($upcomingContracts as $contract)
                    <a href="{{ route('contracts.show', $contract) }}" class="block rounded-xl theme-border-muted theme-bg-card density-px-sm density-py-sm transition-colors hover:theme-bg-secondary">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold theme-text-primary">{{ $contract->title }}</div>
                                <div class="text-xs theme-text-muted">Ends {{ $contract->end_date?->format('M d, Y') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold theme-text-primary">
                                    {{ $contract->days_until_expiration ?? $contract->daysUntilExpiration() ?? '---' }}
                                </div>
                                <div class="text-xs theme-text-muted">days</div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed theme-border-primary density-p-lg text-center text-sm theme-text-muted">
                        No upcoming expirations.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const data = {
            revenueSparkline: @json($revenueSparkline),
            ordersSparkline: @json($ordersSparkline),
            ticketsSparkline: @json($ticketsSparkline),
            invoiceTrend: @json($invoiceTrendChart),
        };

        function initDashboardCharts() {
            if (!window.Chart) return;
            window.__portalCharts = window.__portalCharts || {};

            // Destroy previous instances (Livewire re-renders)
            const chartKeys = ['revenueSparkline', 'ordersSparkline', 'ticketsSparkline', 'revenueOverview'];
            for (const key of chartKeys) {
                if (window.__portalCharts[key]) {
                    try { window.__portalCharts[key].destroy(); } catch (e) {}
                    window.__portalCharts[key] = null;
                }
            }

            // Sparkline chart config
            const sparklineConfig = (data, color) => ({
                type: 'line',
                data: {
                    labels: data.map((_, i) => i),
                    datasets: [{
                        data: data,
                        borderColor: color,
                        borderWidth: 2,
                        fill: true,
                        backgroundColor: color + '20',
                        tension: 0.4,
                        pointRadius: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: {
                        x: { display: false },
                        y: { display: false },
                    },
                },
            });

            // Revenue sparkline
            const revSparkEl = document.getElementById('revenueSparkline');
            if (revSparkEl) {
                window.__portalCharts.revenueSparkline = new Chart(revSparkEl.getContext('2d'), sparklineConfig(data.revenueSparkline || [0,0,0,0,0,0,0], '#5F5F82'));
            }

            // Orders sparkline
            const ordSparkEl = document.getElementById('ordersSparkline');
            if (ordSparkEl) {
                window.__portalCharts.ordersSparkline = new Chart(ordSparkEl.getContext('2d'), sparklineConfig(data.ordersSparkline || [0,0,0,0,0,0,0], '#5F5F82'));
            }

            // Tickets sparkline
            const tickSparkEl = document.getElementById('ticketsSparkline');
            if (tickSparkEl) {
                window.__portalCharts.ticketsSparkline = new Chart(tickSparkEl.getContext('2d'), sparklineConfig(data.ticketsSparkline || [0,0,0,0,0,0,0], '#f59e0b'));
            }

            // Revenue Overview Chart
            const revOverviewEl = document.getElementById('revenueOverviewChart');
            if (revOverviewEl && data.invoiceTrend && data.invoiceTrend.labels) {
                window.__portalCharts.revenueOverview = new Chart(revOverviewEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.invoiceTrend.labels || [],
                        datasets: [{
                            label: 'Revenue',
                            data: data.invoiceTrend.paid || [],
                            backgroundColor: '#5F5F82',
                            borderRadius: 6,
                            barThickness: 24,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#64748b', font: { size: 11 } },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    color: '#64748b',
                                    font: { size: 11 },
                                    callback: (v) => '$' + Number(v).toLocaleString(),
                                },
                            },
                        },
                    },
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initDashboardCharts);
        document.addEventListener('livewire:initialized', initDashboardCharts);
    })();
</script>
@endpush
