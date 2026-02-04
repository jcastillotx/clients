@php
    $isAdminArea = request()->routeIs('admin.*');
@endphp

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 pt-16 lg:pt-0 overflow-y-auto">

    {{-- Logo --}}
    <div class="hidden lg:flex items-center justify-center h-16 border-b border-slate-800 density-px-md flex-shrink-0">
        <a href="{{ $isAdminArea ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-2">
            @if(config('branding.logo.header'))
                <img src="/{{ config('branding.logo.header') }}" alt="Logo" class="h-8">
            @else
                <span class="text-lg font-bold text-white">{{ config('branding.company.name') }}</span>
            @endif
        </a>
    </div>

    @if($isAdminArea)
        {{-- ADMIN NAVIGATION --}}
        <nav class="density-py-md density-px-sm space-y-1" aria-label="Admin navigation">
            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="home" class="w-5 h-5 flex-shrink-0" />
                <span>Dashboard</span>
            </a>

            {{-- Services Section --}}
            <div class="density-pt-md density-pb-sm">
                <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Services</p>
            </div>

            <a href="{{ route('admin.requests.index') }}"
                class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.requests.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="clipboard-list" class="w-5 h-5 flex-shrink-0" />
                <span>Service Requests</span>
            </a>

            <a href="{{ route('admin.support-tickets.index') }}"
                class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.support-tickets.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="question-mark-circle" class="w-5 h-5 flex-shrink-0" />
                <span>Support Tickets</span>
            </a>

            @if(Route::has('admin.maintenance-plans.index'))
                <a href="{{ route('admin.maintenance-plans.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.maintenance-plans.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="shield-check" class="w-5 h-5 flex-shrink-0" />
                    <span>Maintenance Plans</span>
                </a>
            @endif

            {{-- Contracts & Proposals --}}
            @if(Route::has('admin.contracts.index'))
                <a href="{{ route('admin.contracts.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.contracts.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="document" class="w-5 h-5 flex-shrink-0" />
                    <span>Contracts</span>
                </a>
            @endif

            @if(Route::has('admin.proposals.builder'))
                <a href="{{ route('admin.proposals.builder') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.proposals.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="presentation-chart-line" class="w-5 h-5 flex-shrink-0" />
                    <span>Proposals</span>
                </a>
            @endif

            {{-- Financial --}}
            <a href="{{ route('admin.invoices.index') }}"
                class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.invoices.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="currency-dollar" class="w-5 h-5 flex-shrink-0" />
                <span>Invoices & Payments</span>
            </a>

            {{-- Projects & Time Section --}}
            @if(Route::has('admin.projects.time') || Route::has('admin.projects.board') || Route::has('admin.projects.timeline'))
                <div class="density-pt-md density-pb-sm">
                    <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Projects & Time</p>
                </div>

                @if(Route::has('admin.projects.time'))
                    <a href="{{ route('admin.projects.time') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.projects.time*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="clock" class="w-5 h-5 flex-shrink-0" />
                        <span>Time Tracking</span>
                    </a>
                @endif

                @if(Route::has('admin.projects.board'))
                    <a href="{{ route('admin.projects.board') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.projects.board') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="collection" class="w-5 h-5 flex-shrink-0" />
                        <span>Task Board</span>
                    </a>
                @endif

                @if(Route::has('admin.projects.timeline'))
                    <a href="{{ route('admin.projects.timeline') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.projects.timeline') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="calendar" class="w-5 h-5 flex-shrink-0" />
                        <span>Project Timeline</span>
                    </a>
                @endif

                @if(Route::has('admin.projects.budgets'))
                    <a href="{{ route('admin.projects.budgets') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.projects.budgets') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="briefcase" class="w-5 h-5 flex-shrink-0" />
                        <span>Project Budgets</span>
                    </a>
                @endif

                @if(Route::has('admin.tasks.index'))
                    <a href="{{ route('admin.tasks.index') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.tasks.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="clipboard-list" class="w-5 h-5 flex-shrink-0" />
                        <span>Staff Tasks</span>
                    </a>
                @endif
            @endif

            {{-- Communication Section --}}
            <div class="density-pt-md density-pb-sm">
                <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Communication</p>
            </div>

            @if(Route::has('admin.messages'))
                <a href="{{ route('admin.messages') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.messages') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="chat" class="w-5 h-5 flex-shrink-0" />
                    <span>Messages</span>
                </a>
            @endif

            @if(Route::has('admin.meetings'))
                <a href="{{ route('admin.meetings') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.meetings') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="calendar" class="w-5 h-5 flex-shrink-0" />
                    <span>Meetings</span>
                </a>
            @endif

            @if(Route::has('admin.meeting-notes'))
                <a href="{{ route('admin.meeting-notes') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.meeting-notes') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="annotation" class="w-5 h-5 flex-shrink-0" />
                    <span>Meeting Notes</span>
                </a>
            @endif

            @if(Route::has('admin.communication.email-assistant'))
                <a href="{{ route('admin.communication.email-assistant') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.communication.email-assistant') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="envelope" class="w-5 h-5 flex-shrink-0" />
                    <span>Email Assistant</span>
                </a>
            @endif

            {{-- Marketing & Analytics Section --}}
            @if(Route::has('admin.marketing.website-auditor') || Route::has('admin.social.posts') || Route::has('admin.ads.campaigns') || Route::has('admin.brand-monitoring.dashboard'))
                <div class="density-pt-md density-pb-sm">
                    <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Marketing</p>
                </div>

                @if(Route::has('admin.marketing.website-auditor'))
                    <a href="{{ route('admin.marketing.website-auditor') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.marketing.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="speakerphone" class="w-5 h-5 flex-shrink-0" />
                        <span>Marketing Tools</span>
                    </a>
                @endif

                @if(Route::has('admin.social.posts'))
                    <a href="{{ route('admin.social.posts') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.social.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="heart" class="w-5 h-5 flex-shrink-0" />
                        <span>Social Media</span>
                    </a>
                @endif

                @if(Route::has('admin.ads.campaigns'))
                    <a href="{{ route('admin.ads.campaigns') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.ads.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="trending-up" class="w-5 h-5 flex-shrink-0" />
                        <span>Ad Management</span>
                    </a>
                @endif

                @if(Route::has('admin.brand-monitoring.dashboard'))
                    <a href="{{ route('admin.brand-monitoring.dashboard') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.brand-monitoring.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="globe" class="w-5 h-5 flex-shrink-0" />
                        <span>Brand Monitoring</span>
                    </a>
                @endif
            @endif

            {{-- AI & Automation Section --}}
            @if(Route::has('admin.ai.providers') || Route::has('admin.automation.index') || Route::has('admin.analytics.ai-insights'))
                <div class="density-pt-md density-pb-sm">
                    <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">AI & Automation</p>
                </div>

                @if(Route::has('admin.ai.providers'))
                    <a href="{{ route('admin.ai.providers') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.ai.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="beaker" class="w-5 h-5 flex-shrink-0" />
                        <span>AI Management</span>
                    </a>
                @endif

                @if(Route::has('admin.ai.assistant'))
                    <a href="{{ route('admin.ai.assistant') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.ai.assistant') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="chat" class="w-5 h-5 flex-shrink-0" />
                        <span>AI Assistant</span>
                    </a>
                @endif

                @if(Route::has('admin.automation.index'))
                    <a href="{{ route('admin.automation.index') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.automation.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="lightning-bolt" class="w-5 h-5 flex-shrink-0" />
                        <span>Automation</span>
                    </a>
                @endif

                @if(Route::has('admin.analytics.ai-insights'))
                    <a href="{{ route('admin.analytics.ai-insights') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.analytics.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="chip" class="w-5 h-5 flex-shrink-0" />
                        <span>AI Analytics</span>
                    </a>
                @endif
            @endif

            {{-- Reports Section --}}
            @if(Route::has('admin.reports') || Route::has('admin.workload') || Route::has('admin.activity'))
                <div class="density-pt-md density-pb-sm">
                    <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Reports</p>
                </div>

                @if(Route::has('admin.reports'))
                    <a href="{{ route('admin.reports') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reports') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="chart-bar" class="w-5 h-5 flex-shrink-0" />
                        <span>Reports Dashboard</span>
                    </a>
                @endif

                @if(Route::has('admin.workload'))
                    <a href="{{ route('admin.workload') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.workload') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="users" class="w-5 h-5 flex-shrink-0" />
                        <span>Team Workload</span>
                    </a>
                @endif

                @if(Route::has('admin.client-reports'))
                    <a href="{{ route('admin.client-reports') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.client-reports') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="template" class="w-5 h-5 flex-shrink-0" />
                        <span>Client Reports</span>
                    </a>
                @endif

                @if(Route::has('admin.activity'))
                    <a href="{{ route('admin.activity') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.activity') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="clock" class="w-5 h-5 flex-shrink-0" />
                        <span>Activity Log</span>
                    </a>
                @endif
            @endif

            {{-- Admin Management Section --}}
            <div class="density-pt-md density-pb-sm">
                <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Management</p>
            </div>

            <a href="{{ route('admin.clients.index') }}"
                class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.clients.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="office-building" class="w-5 h-5 flex-shrink-0" />
                <span>Clients</span>
            </a>

            <a href="{{ route('admin.users.index') }}"
                class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="users" class="w-5 h-5 flex-shrink-0" />
                <span>Users</span>
            </a>

            @if(Route::has('admin.partners'))
                <a href="{{ route('admin.partners') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.partners') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="link" class="w-5 h-5 flex-shrink-0" />
                    <span>Partners</span>
                </a>
            @endif

            @if(Route::has('admin.referrals'))
                <a href="{{ route('admin.referrals') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.referrals') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="star" class="w-5 h-5 flex-shrink-0" />
                    <span>Referrals</span>
                </a>
            @endif

            @if(Route::has('admin.staff-guides'))
                <a href="{{ route('admin.staff-guides') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.staff-guides') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="academic-cap" class="w-5 h-5 flex-shrink-0" />
                    <span>Staff Guides</span>
                </a>
            @endif

            {{-- Feedback & Surveys --}}
            @if(Route::has('admin.feedback.surveys'))
                <a href="{{ route('admin.feedback.surveys') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.feedback.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="annotation" class="w-5 h-5 flex-shrink-0" />
                    <span>Feedback & Surveys</span>
                </a>
            @endif

            {{-- Account Management --}}
            @if(Route::has('admin.account.health'))
                <a href="{{ route('admin.account.health') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.account.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="heart" class="w-5 h-5 flex-shrink-0" />
                    <span>Account Health</span>
                </a>
            @endif

            {{-- Storage & Integration Section --}}
            @if(Route::has('admin.storage') || Route::has('admin.webhooks.index'))
                <div class="density-pt-md density-pb-sm">
                    <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Storage & Integration</p>
                </div>

                @if(Route::has('admin.storage'))
                    <a href="{{ route('admin.storage') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.storage*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="server" class="w-5 h-5 flex-shrink-0" />
                        <span>Storage Management</span>
                    </a>
                @endif

                @if(Route::has('admin.webhooks.index'))
                    <a href="{{ route('admin.webhooks.index') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.webhooks.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="link" class="w-5 h-5 flex-shrink-0" />
                        <span>Webhooks</span>
                    </a>
                @endif
            @endif

            {{-- Security Section --}}
            @if(Route::has('admin.security.overview') || Route::has('admin.security.privacy-requests'))
                <div class="density-pt-md density-pb-sm">
                    <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Security</p>
                </div>

                @if(Route::has('admin.security.overview'))
                    <a href="{{ route('admin.security.overview') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.security.overview') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="shield-check" class="w-5 h-5 flex-shrink-0" />
                        <span>Security Overview</span>
                    </a>
                @endif

                @if(Route::has('admin.security.privacy-requests'))
                    <a href="{{ route('admin.security.privacy-requests') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.security.privacy-requests') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="eye-off" class="w-5 h-5 flex-shrink-0" />
                        <span>Privacy Requests</span>
                    </a>
                @endif
            @endif

            {{-- Settings Section --}}
            @can('manage settings')
                <div class="density-pt-md density-pb-sm">
                    <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Settings</p>
                </div>

                @if(Route::has('admin.settings.index'))
                    <a href="{{ route('admin.settings.index') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="cog" class="w-5 h-5 flex-shrink-0" />
                        <span>System Settings</span>
                    </a>
                @endif

                @if(Route::has('admin.settings.forms'))
                    <a href="{{ route('admin.settings.forms') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.settings.forms*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="template" class="w-5 h-5 flex-shrink-0" />
                        <span>Form Templates</span>
                    </a>
                @endif

                @if(Route::has('admin.white-label'))
                    <a href="{{ route('admin.white-label') }}"
                        class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.white-label') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <x-icon name="template" class="w-5 h-5 flex-shrink-0" />
                        <span>White Label</span>
                    </a>
                @endif
            @endcan
        </nav>
    @else
        {{-- CLIENT NAVIGATION --}}
        <nav class="density-py-md density-px-sm space-y-1" aria-label="Client navigation">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon name="home" class="w-5 h-5 flex-shrink-0" />
                <span>Dashboard</span>
            </a>

            {{-- Services Section --}}
            <div class="density-pt-md density-pb-sm">
                <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Services</p>
            </div>

            @if(Route::has('requests.index'))
                <a href="{{ route('requests.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('requests.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="clipboard-list" class="w-5 h-5 flex-shrink-0" />
                    <span>My Requests</span>
                </a>
            @endif

            @if(Route::has('support-tickets.index'))
                <a href="{{ route('support-tickets.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('support-tickets.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="question-mark-circle" class="w-5 h-5 flex-shrink-0" />
                    <span>Support</span>
                </a>
            @endif

            @if(Route::has('invoices.index'))
                <a href="{{ route('invoices.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('invoices.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="currency-dollar" class="w-5 h-5 flex-shrink-0" />
                    <span>Invoices</span>
                </a>
            @endif

            {{-- Projects & Files --}}
            <div class="density-pt-md density-pb-sm">
                <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Projects</p>
            </div>

            @if(Route::has('projects.index'))
                <a href="{{ route('projects.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('projects.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="folder-open" class="w-5 h-5 flex-shrink-0" />
                    <span>Projects</span>
                </a>
            @endif

            @if(Route::has('files.index'))
                <a href="{{ route('files.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('files.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="document-text" class="w-5 h-5 flex-shrink-0" />
                    <span>Files</span>
                </a>
            @endif

            @if(Route::has('contracts.index'))
                <a href="{{ route('contracts.index') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('contracts.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="document" class="w-5 h-5 flex-shrink-0" />
                    <span>Contracts</span>
                </a>
            @endif

            {{-- Account Section --}}
            <div class="density-pt-md density-pb-sm">
                <p class="density-px-sm text-xs font-semibold text-slate-500 uppercase tracking-wider">Account</p>
            </div>

            @if(Route::has('profile.edit'))
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-3 density-px-sm density-py-sm text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('profile.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <x-icon name="user-circle" class="w-5 h-5 flex-shrink-0" />
                    <span>Profile</span>
                </a>
            @endif
        </nav>
    @endif
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
