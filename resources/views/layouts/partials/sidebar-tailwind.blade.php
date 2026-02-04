{{-- Tailwind Admin Sidebar --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 pt-16 lg:pt-0 overflow-y-auto">

    {{-- Logo --}}
    <div class="hidden lg:flex items-center justify-center h-16 border-b border-slate-800 px-4 flex-shrink-0">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            @if(config('branding.logo.header'))
                <img src="/{{ config('branding.logo.header') }}" alt="Logo" class="h-8">
            @else
                <span class="text-lg font-bold text-white">{{ config('branding.company.name') }}</span>
            @endif
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="py-4 px-3 space-y-1" aria-label="Admin navigation">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <x-icon name="home" class="w-5 h-5 flex-shrink-0" />
            <span>Dashboard</span>
        </a>

        {{-- Services Section --}}
        <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Services</p>
        </div>

        <a href="{{ route('admin.requests.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.requests.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <x-icon name="clipboard-list" class="w-5 h-5 flex-shrink-0" />
            <span>Service Requests</span>
        </a>

        <a href="{{ route('admin.support-tickets.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.support-tickets.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <x-icon name="question-mark-circle" class="w-5 h-5 flex-shrink-0" />
            <span>Support Tickets</span>
        </a>

        <a href="{{ route('admin.invoices.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.invoices.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <x-icon name="currency-dollar" class="w-5 h-5 flex-shrink-0" />
            <span>Invoices & Payments</span>
        </a>

        {{-- Admin Section --}}
        <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Admin</p>
        </div>

        <a href="{{ route('admin.clients.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.clients.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <x-icon name="office-building" class="w-5 h-5 flex-shrink-0" />
            <span>Clients</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <x-icon name="users" class="w-5 h-5 flex-shrink-0" />
            <span>Users</span>
        </a>

        @if(Route::has('admin.messages'))
            <a href="{{ route('admin.messages') }}"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.messages') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="chat" class="w-5 h-5 flex-shrink-0" />
                <span>Messages</span>
            </a>
        @endif

        @if(Route::has('admin.reports.dashboard'))
            <a href="{{ route('admin.reports.dashboard') }}"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reports.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="chart-bar" class="w-5 h-5 flex-shrink-0" />
                <span>Reporting</span>
            </a>
        @endif

        {{-- Settings Section --}}
        @can('manage settings')
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Settings</p>
            </div>

            <a href="{{ route('admin.settings.index') }}"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="cog" class="w-5 h-5 flex-shrink-0" />
                <span>System Settings</span>
            </a>
        @endcan
    </nav>
</aside>

{{-- Overlay for mobile --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 lg:hidden"
     aria-hidden="true">
</div>