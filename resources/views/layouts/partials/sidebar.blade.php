<aside class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 pt-16 lg:pt-0">
    <!-- Brand Logo -->
    @php
        $isAdminArea = request()->routeIs('admin.*');
        $logo = config('branding.admin.dashboard_logo') ?: config('branding.logo.main');
    @endphp
    <div class="hidden lg:flex items-center justify-center h-16 border-b border-slate-800 px-4">
        <a href="{{ $isAdminArea ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-2">
            @if(!empty($logo))
                <img src="{{ asset($logo) }}" alt="{{ config('branding.company.name') }}" class="h-8">
            @else
                <span class="text-lg font-bold text-white">
                    {{ $isAdminArea ? 'Admin' : 'Client Portal' }}
                </span>
            @endif
        </a>
    </div>

    <!-- Sidebar Content -->
    <div class="flex-1 overflow-y-auto">
        <!-- Sidebar user panel -->
        <div class="flex items-center gap-3 px-4 py-4 border-b border-slate-800">
            <div class="flex-shrink-0">
                @php $u = auth()->user(); $photo = $u?->profilePhotoUrl(); @endphp
                @if($photo)
                    <img src="{{ $photo }}" alt="Profile photo" class="w-10 h-10 rounded-full object-cover">
                @else
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                        <span class="text-white font-bold text-sm">{{ $u->initials }}</span>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <a href="{{ route('profile.edit') }}" class="block text-sm font-medium text-white hover:text-slate-200 truncate">{{ auth()->user()->name }}</a>
                @if(auth()->user()->client)
                <p class="text-xs text-slate-400 truncate">{{ auth()->user()->client->company_name }}</p>
                @endif
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="py-4 px-3 space-y-1">
            {{-- ============================================= --}}
            {{-- DASHBOARD --}}
            {{-- ============================================= --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-tachometer-alt w-5"></i>
                <span>Dashboard</span>
            </a>

            {{-- ============================================= --}}
            {{-- SERVICES SECTION --}}
            {{-- ============================================= --}}
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Services</p>
            </div>

            <!-- Service Requests -->
            @platformFeature('service_requests')
            <a href="{{ route('requests.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('requests.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <i class="fas fa-clipboard-list w-5"></i>
                    <span>Service Requests</span>
                </div>
                @php
                    $openRequests = 0;
                    if (auth()->user()->client) {
                        $openRequests = \App\Models\Request::where('client_id', auth()->user()->client_id)->open()->count();
                    }
                @endphp
                @if($openRequests > 0)
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">{{ $openRequests }}</span>
                @endif
            </a>
            @endplatformFeature

            <!-- Support Tickets -->
            <a href="{{ route('support-tickets.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('support-tickets.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <i class="fas fa-life-ring w-5"></i>
                    <span>Support Tickets</span>
                </div>
                @php
                    $openTickets = 0;
                    if (auth()->user()->client) {
                        $openTickets = \App\Models\SupportTicket::where('client_id', auth()->user()->client_id)->open()->count();
                    }
                @endphp
                @if($openTickets > 0)
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-500 rounded-full">{{ $openTickets }}</span>
                @endif
            </a>

            <!-- Contracts -->
            @platformFeature('contracts')
            <a href="{{ route('contracts.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('contracts.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <i class="fas fa-file-contract w-5"></i>
                    <span>Contracts</span>
                </div>
                @php
                    $pendingContracts = 0;
                    if (auth()->user()->client) {
                        $pendingContracts = \App\Models\Contract::where('client_id', auth()->user()->client_id)->pendingSignature()->count();
                    }
                @endphp
                @if($pendingContracts > 0)
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-500 rounded-full">{{ $pendingContracts }}</span>
                @endif
            </a>
            @endplatformFeature

            <!-- Invoices -->
            @platformFeature('invoices')
            @if(auth()->user()?->can('access admin panel'))
                <a href="{{ route('admin.invoices.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.invoices.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fas fa-file-invoice-dollar w-5"></i>
                    <span>Invoices & Payments</span>
                </a>
            @else
                <a href="{{ route('invoices.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('invoices.*') || request()->routeIs('payments.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file-invoice-dollar w-5"></i>
                        <span>Invoices</span>
                    </div>
                    @php
                        $unpaidInvoices = 0;
                        if (auth()->user()->client) {
                            $unpaidInvoices = \App\Models\Invoice::where('client_id', auth()->user()->client_id)->unpaid()->count();
                        }
                    @endphp
                    @if($unpaidInvoices > 0)
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full">{{ $unpaidInvoices }}</span>
                    @endif
                </a>
            @endif
            @endplatformFeature

            <!-- Documents -->
            @platformFeature('documents')
            <div x-data="{ open: {{ request()->routeIs('documents.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('documents.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-folder-open w-5"></i>
                        <span>Documents</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ 'rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('documents.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('documents.index') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>My Documents</span>
                    </a>
                    @if(auth()->user()?->client_id || auth()->user()?->can('access admin panel'))
                        <a href="{{ route('documents.smart-browser') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('documents.smart-browser') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                            <i class="far fa-circle text-xs w-5"></i>
                            <span>Smart Browser</span>
                        </a>
                    @endif
                    @can('access admin panel')
                    <a href="{{ route('documents.templates') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('documents.templates') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Templates</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endplatformFeature

            {{-- ============================================= --}}
            {{-- CLIENT SECTION (Client users only) --}}
            {{-- ============================================= --}}
            @if(auth()->user()->isClient())
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</p>
            </div>

            <a href="{{ route('client.projects') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.projects') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-project-diagram w-5"></i>
                <span>Projects</span>
            </a>
            <a href="{{ route('client.onboarding') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.onboarding') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-list-check w-5"></i>
                <span>Onboarding</span>
            </a>
            <a href="{{ route('client.messaging') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.messaging') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-comments w-5"></i>
                <span>Messages</span>
            </a>
            <a href="{{ route('client.meetings') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.meetings') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-calendar w-5"></i>
                <span>Meetings</span>
            </a>
            <a href="{{ route('client.knowledge-base') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.knowledge-base') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-book w-5"></i>
                <span>Knowledge Base</span>
            </a>

            {{-- ============================================= --}}
            {{-- MARKETING SECTION (Client marketing tools) --}}
            {{-- ============================================= --}}
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Marketing</p>
            </div>

            <!-- SEO Dashboard -->
            <a href="{{ route('client.seo') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.seo') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-search-dollar w-5"></i>
                <span>SEO Dashboard</span>
            </a>

            <!-- Campaigns -->
            <div x-data="{ open: {{ request()->routeIs('client.campaigns*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.campaigns*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-rocket w-5"></i>
                        <span>Campaigns</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('client.campaigns') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('client.campaigns') && !request()->routeIs('client.campaigns.manage') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('client.campaigns.manage') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('client.campaigns.manage') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Manage Campaigns</span>
                    </a>
                </div>
            </div>

            <!-- Brand Monitoring -->
            @if(auth()->user()->client && auth()->user()->client->hasFeature('brand_monitoring'))
            <a href="{{ route('client.brand-monitoring.my-mentions') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.brand-monitoring.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-bullhorn w-5"></i>
                <span>Brand Monitor</span>
            </a>
            @endif

            <!-- Social Media Management -->
            <div x-data="{ open: {{ request()->routeIs('social.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('social.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-share-alt w-5"></i>
                        <span>Social Media</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('social.accounts') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('social.accounts') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Accounts</span>
                    </a>
                    <a href="{{ route('social.pending-approvals') }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('social.pending-approvals') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <div class="flex items-center gap-3">
                            <i class="far fa-circle text-xs w-5"></i>
                            <span>Approvals</span>
                        </div>
                        @php
                            $pendingPosts = 0;
                            if (auth()->user()->client) {
                                $pendingPosts = \App\Models\ContentCalendarItem::forClient(auth()->user()->client_id)->pendingApproval()->count();
                            }
                        @endphp
                        @if($pendingPosts > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-500 rounded-full">{{ $pendingPosts }}</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- ============================================= --}}
            {{-- REPORTS SECTION (Client analytics) --}}
            {{-- ============================================= --}}
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reports</p>
            </div>

            <a href="{{ route('client.analytics') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.analytics') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-chart-pie w-5"></i>
                <span>Analytics</span>
            </a>
            <a href="{{ route('client.reports') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.reports') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-chart-line w-5"></i>
                <span>Reports</span>
            </a>
            <a href="{{ route('client.reports.archive') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.reports.archive') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-archive w-5"></i>
                <span>Report Archive</span>
            </a>

            {{-- ============================================= --}}
            {{-- STORAGE SECTION (Client cloud storage) --}}
            {{-- ============================================= --}}
            @php
                $navUser = auth()->user();
            @endphp
            @if($navUser?->client_id)
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Storage</p>
            </div>

            <div x-data="{ open: {{ request()->routeIs('storage.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('storage.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-cloud w-5"></i>
                        <span>Cloud Storage</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('storage.dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('storage.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('storage.browser') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('storage.browser') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>File Browser</span>
                    </a>
                    <a href="{{ route('storage.conflicts') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('storage.conflicts') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Conflicts</span>
                    </a>
                    <a href="{{ route('storage.settings') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('storage.settings') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
            @endif
            @endif

            {{-- ============================================= --}}
            {{-- ADMIN SECTION --}}
            {{-- ============================================= --}}
            @can('access admin panel')
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Admin</p>
            </div>

            <a href="{{ route('admin.clients.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.clients.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-building w-5"></i>
                <span>Clients</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users.*') && !request()->routeIs('admin.users.permissions') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-users w-5"></i>
                <span>Users</span>
            </a>
            <a href="{{ route('admin.users.permissions') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users.permissions') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-key w-5"></i>
                <span>Permissions</span>
            </a>

            @platformFeature('messaging')
            <a href="{{ route('admin.messages') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.messages') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-comments w-5"></i>
                <span>Messages</span>
            </a>
            @endplatformFeature

            @platformFeature('meetings')
            <a href="{{ route('admin.meetings') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.meetings') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-calendar-alt w-5"></i>
                <span>Meetings</span>
            </a>
            @endplatformFeature

            @platformFeature('reporting')
            <a href="{{ route('admin.reports.dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-chart-line w-5"></i>
                <span>Reporting</span>
            </a>
            @endplatformFeature

            @platformFeature('projects')
            <div x-data="{ open: {{ request()->routeIs('admin.projects.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.projects.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-stopwatch w-5"></i>
                        <span>Projects</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.projects.time') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.projects.time') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Time Tracker</span>
                    </a>
                    <a href="{{ route('admin.projects.time-approvals') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.projects.time-approvals') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Time Approvals</span>
                    </a>
                    <a href="{{ route('admin.projects.budgets') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.projects.budgets') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Budgets</span>
                    </a>
                    <a href="{{ route('admin.projects.board') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.projects.board') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Task Board</span>
                    </a>
                    <a href="{{ route('admin.projects.timeline') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.projects.timeline') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Timeline</span>
                    </a>
                    <a href="{{ route('admin.projects.workload') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.projects.workload') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Team Workload</span>
                    </a>
                </div>
            </div>
            @endplatformFeature

            <!-- Staff Task Management -->
            <a href="{{ route('admin.tasks.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.tasks.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-tasks w-5"></i>
                <span>Task Management</span>
            </a>

            {{-- ============================================= --}}
            {{-- SUPPORT SECTION (Admin) --}}
            {{-- ============================================= --}}
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Support</p>
            </div>

            <!-- Support Tickets (Admin) -->
            <a href="{{ route('admin.support-tickets.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.support-tickets.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <i class="fas fa-headset w-5"></i>
                    <span>Support Tickets</span>
                </div>
                @php
                    $openAdminTickets = \App\Models\SupportTicket::open()->count();
                @endphp
                @if($openAdminTickets > 0)
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-500 rounded-full">{{ $openAdminTickets }}</span>
                @endif
            </a>

            <!-- Maintenance Plans -->
            <a href="{{ route('admin.maintenance-plans.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.maintenance-plans.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-tools w-5"></i>
                <span>Maintenance Plans</span>
            </a>

            {{-- ============================================= --}}
            {{-- SALES SECTION (Admin) --}}
            {{-- ============================================= --}}
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sales</p>
            </div>

            @platformFeature('proposals')
            <div x-data="{ open: {{ request()->routeIs('admin.proposals.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.proposals.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file-signature w-5"></i>
                        <span>Proposals</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.proposals.builder') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.proposals.builder') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Builder</span>
                    </a>
                </div>
            </div>
            @endplatformFeature

            @platformFeature('contracts')
            @if(Route::has('admin.contracts.index'))
            <div x-data="{ open: {{ request()->routeIs('admin.contracts.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.contracts.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file-contract w-5"></i>
                        <span>Contracts</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.contracts.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.contracts.index') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>All Contracts</span>
                    </a>
                    <a href="{{ route('admin.contracts.create') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.contracts.create') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>New Contract</span>
                    </a>
                    <a href="{{ route('admin.contracts.generator') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.contracts.generator') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>AI Generator</span>
                    </a>
                </div>
            </div>
            @endif
            @endplatformFeature

            @platformFeature('feedback')
            <div x-data="{ open: {{ request()->routeIs('admin.feedback.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.feedback.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-poll w-5"></i>
                        <span>Feedback</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.feedback.surveys') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.feedback.surveys') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Surveys</span>
                    </a>
                    <a href="{{ route('admin.feedback.testimonials') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.feedback.testimonials') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Testimonials</span>
                    </a>
                </div>
            </div>
            @endplatformFeature

            @platformFeature('account_management')
            <div x-data="{ open: {{ request()->routeIs('admin.account.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.account.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-handshake w-5"></i>
                        <span>Account Mgmt</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.account.health') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.account.health') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Health</span>
                    </a>
                    <a href="{{ route('admin.account.qbrs') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.account.qbrs') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>QBRs</span>
                    </a>
                    <a href="{{ route('admin.account.renewals') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.account.renewals') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Renewals</span>
                    </a>
                    <a href="{{ route('admin.account.upsells') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.account.upsells') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Upsells</span>
                    </a>
                </div>
            </div>
            @endplatformFeature

            <!-- Staff How-To Guides -->
            @can('access admin panel')
            <a href="{{ route('admin.staff-guides') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.staff-guides*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-book-reader w-5"></i>
                <span>Staff Guides</span>
            </a>
            @endcan

            {{-- ============================================= --}}
            {{-- MARKETING SECTION (Admin marketing tools) --}}
            {{-- ============================================= --}}
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Marketing</p>
            </div>

            @can('view_any_lead')
            <a href="{{ route('admin.marketing.leads') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.marketing.leads') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-user-plus w-5"></i>
                <span>Leads</span>
            </a>
            @endcan

            <!-- Campaigns -->
            <div x-data="{ open: {{ request()->routeIs('admin.marketing.campaigns*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.marketing.campaigns*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-rocket w-5"></i>
                        <span>Campaigns</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.marketing.campaigns') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.marketing.campaigns') && !request()->routeIs('admin.marketing.campaigns.manage') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Analytics</span>
                    </a>
                    <a href="{{ route('admin.marketing.campaigns.manage') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.marketing.campaigns.manage') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Manage Campaigns</span>
                    </a>
                </div>
            </div>

            <!-- Brand Monitoring -->
            <div x-data="{ open: {{ request()->routeIs('admin.brand-monitoring.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.brand-monitoring.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-bullhorn w-5"></i>
                        <span>Brand Monitor</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.brand-monitoring.dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.brand-monitoring.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.brand-monitoring.api-status') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.brand-monitoring.api-status') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>API Status</span>
                    </a>
                </div>
            </div>

            <!-- Social Media -->
            <div x-data="{ open: {{ request()->routeIs('admin.social.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.social.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-share-alt w-5"></i>
                        <span>Social Media</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.social.posts') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.social.posts') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Posts</span>
                    </a>
                    <a href="{{ route('admin.social.posts.create') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.social.posts.create') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Create Post</span>
                    </a>
                    <a href="{{ route('admin.social.content-calendar') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.social.content-calendar') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Calendar</span>
                    </a>
                </div>
            </div>

            @platformFeature('partners')
            <div x-data="{ open: {{ request()->routeIs('admin.partners') || request()->routeIs('admin.referrals') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.partners') || request()->routeIs('admin.referrals') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user-friends w-5"></i>
                        <span>Partners</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.partners') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.partners') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Partners</span>
                    </a>
                    <a href="{{ route('admin.referrals') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.referrals') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Referrals</span>
                    </a>
                </div>
            </div>
            @endplatformFeature

            {{-- ============================================= --}}
            {{-- AI SECTION (Admin AI tools) --}}
            {{-- ============================================= --}}
            @platformFeature('ai')
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">AI Tools</p>
            </div>

            <a href="{{ route('admin.ai.assistant') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.ai.assistant') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-robot w-5"></i>
                <span>AI Assistant</span>
            </a>

            <div x-data="{ open: {{ request()->routeIs('admin.ai.*') && !request()->routeIs('admin.ai.assistant') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.ai.*') && !request()->routeIs('admin.ai.assistant') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-brain w-5"></i>
                        <span>AI Management</span>
                    </div>
                    <i class="fas fa-angle-left transition-transform" :class="{ '-rotate-90': open }"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('admin.ai.providers') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.providers*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Providers</span>
                    </a>
                    <a href="{{ route('admin.ai.tasks') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.tasks') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Task Config</span>
                    </a>
                    <a href="{{ route('admin.ai.usage') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.usage') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Usage & Costs</span>
                    </a>
                    <a href="{{ route('admin.ai.quality') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.quality') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Quality Metrics</span>
                    </a>
                    <a href="{{ route('admin.ai.safety') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.safety') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Safety Dashboard</span>
                    </a>
                    <a href="{{ route('admin.ai.review-queue') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.review-queue') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Review Queue</span>
                    </a>
                    <a href="{{ route('admin.ai.knowledge-base') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.knowledge-base') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Knowledge Base</span>
                    </a>
                    <a href="{{ route('admin.ai.prompt-templates') }}" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.ai.prompt-templates') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200' }}">
                        <i class="far fa-circle text-xs w-5"></i>
                        <span>Prompt Templates</span>
                    </a>
                </div>
            </div>
            @endplatformFeature

            {{-- ============================================= --}}
            {{-- SETTINGS SECTION (Admin system settings) --}}
            {{-- ============================================= --}}
            @can('manage settings')
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Settings</p>
            </div>

            <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.settings') || request()->routeIs('admin.settings.index') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-cogs w-5"></i>
                <span>System Settings</span>
            </a>
            <a href="{{ route('admin.settings.forms') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.settings.forms*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-wpforms w-5"></i>
                <span>Form Templates</span>
            </a>
            <a href="{{ route('admin.security.overview') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.security.overview') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-shield-alt w-5"></i>
                <span>Security Settings</span>
            </a>
            <a href="{{ route('admin.security.privacy-requests') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.security.privacy-requests') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-user-shield w-5"></i>
                <span>Privacy Requests</span>
            </a>
            @platformFeature('storage_integrations')
            <a href="{{ route('admin.storage.overview') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.storage.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-database w-5"></i>
                <span>Storage Overview</span>
            </a>
            @endplatformFeature
            @platformFeature('webhooks')
            <a href="{{ route('admin.webhooks.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.webhooks.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-plug w-5"></i>
                <span>Webhooks</span>
            </a>
            @endplatformFeature
            @platformFeature('automation')
            <a href="{{ route('admin.automation.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.automation.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-robot w-5"></i>
                <span>Automation</span>
            </a>
            @endplatformFeature
            @endcan
            @endcan

            {{-- ============================================= --}}
            {{-- ACCOUNT SECTION (User personal settings) --}}
            {{-- ============================================= --}}
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Account</p>
            </div>

            <!-- Profile -->
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('profile.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-user-cog w-5"></i>
                <span>Profile Settings</span>
            </a>

            @if(auth()->user()->isClient())
            <a href="{{ route('client.notifications') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.notifications') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="far fa-bell w-5"></i>
                <span>Notifications</span>
            </a>
            <a href="{{ route('client.privacy') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('client.privacy') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-user-shield w-5"></i>
                <span>Privacy</span>
            </a>
            @endif

            <a href="{{ route('two-factor.setup') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('two-factor.setup') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-shield-alt w-5"></i>
                <span>Two-factor (2FA)</span>
            </a>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
                @csrf
            </form>
            <button onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();" class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span>Sign Out</span>
            </button>
        </nav>
    </div>
</aside>
