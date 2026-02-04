{{-- Tailwind Sidebar --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 pt-16 lg:pt-0">

    {{-- Logo --}}
    <div class="hidden lg:flex items-center justify-center h-16 border-b border-slate-800 px-4">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            @if(config('branding.logo.header'))
                <img src="/{{ config('branding.logo.header') }}" alt="Logo" class="h-8">
            @else
                <span class="text-lg font-bold text-white">{{ config('branding.company.name') }}</span>
            @endif
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-home w-5"></i>
            <span>Dashboard</span>
        </a>

        {{-- Services Section --}}
        <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Services</p>
        </div>

        <a href="{{ route('admin.requests.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.requests.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-tasks w-5"></i>
            <span>Service Requests</span>
        </a>

        <a href="{{ route('admin.support-tickets.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.support-tickets.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-life-ring w-5"></i>
            <span>Support Tickets</span>
        </a>

        <a href="{{ route('admin.invoices.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.invoices.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-file-invoice-dollar w-5"></i>
            <span>Invoices & Payments</span>
        </a>

        {{-- Admin Section --}}
        <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Admin</p>
        </div>

        <a href="{{ route('admin.clients.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.clients.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-users w-5"></i>
            <span>Clients</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-user-friends w-5"></i>
            <span>Users</span>
        </a>

        <a href="{{ route('admin.messages') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.messages') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-comments w-5"></i>
            <span>Messages</span>
        </a>

        <a href="{{ route('admin.reports') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.reports*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-chart-line w-5"></i>
            <span>Reporting</span>
        </a>

        {{-- Settings Section --}}
        <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Settings</p>
        </div>

        <a href="{{ route('admin.settings.index') }}"
            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.settings.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-cog w-5"></i>
            <span>System Settings</span>
        </a>
    </nav>
</aside>

{{-- Overlay for mobile --}}
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 lg:hidden">
</div>