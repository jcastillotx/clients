<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    @php
        $isAdminArea = request()->routeIs('admin.*');
        $logo = config('branding.admin.dashboard_logo') ?: config('branding.logo.main');
    @endphp
    <a href="{{ $isAdminArea ? route('admin.dashboard') : route('dashboard') }}" class="brand-link {{ !empty($logo) ? 'text-center' : '' }}">
        @if(!empty($logo))
            <img src="{{ asset($logo) }}" alt="{{ config('branding.company.name') }}" class="brand-image img-circle elevation-3 mx-auto" style="opacity: .85; margin-right: 0 !important; display: block;" onerror="this.style.display='none'">
        @else
            <span class="brand-text font-weight-light">
                {{ $isAdminArea ? 'Admin' : 'Client Portal' }}
            </span>
        @endif
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                @php $u = auth()->user(); $photo = $u?->profilePhotoUrl(); @endphp
                @if($photo)
                    <img src="{{ $photo }}" alt="Profile photo" class="img-circle elevation-2" style="width: 34px; height: 34px; object-fit: cover;">
                @else
                    <div class="img-circle elevation-2 bg-info d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <span class="text-white font-weight-bold">{{ $u->initials }}</span>
                    </div>
                @endif
            </div>
            <div class="info">
                <a href="{{ route('profile.edit') }}" class="d-block">{{ auth()->user()->name }}</a>
                @if(auth()->user()->client)
                <small class="text-muted">{{ auth()->user()->client->company_name }}</small>
                @endif
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                {{-- ============================================= --}}
                {{-- DASHBOARD --}}
                {{-- ============================================= --}}
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- ============================================= --}}
                {{-- SERVICES SECTION --}}
                {{-- ============================================= --}}
                <li class="nav-header">SERVICES</li>

                <!-- Service Requests -->
                @platformFeature('service_requests')
                <li class="nav-item">
                    <a href="{{ route('requests.index') }}" class="nav-link {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>
                            Service Requests
                            @php
                                $openRequests = 0;
                                if (auth()->user()->client) {
                                    $openRequests = \App\Models\Request::where('client_id', auth()->user()->client_id)
                                        ->open()
                                        ->count();
                                }
                            @endphp
                            @if($openRequests > 0)
                            <span class="badge badge-info right">{{ $openRequests }}</span>
                            @endif
                        </p>
                    </a>
                </li>
                @endplatformFeature

                <!-- Contracts -->
                @platformFeature('contracts')
                <li class="nav-item">
                    <a href="{{ route('contracts.index') }}" class="nav-link {{ request()->routeIs('contracts.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-contract"></i>
                        <p>
                            Contracts
                            @php
                                $pendingContracts = 0;
                                if (auth()->user()->client) {
                                    $pendingContracts = \App\Models\Contract::where('client_id', auth()->user()->client_id)
                                        ->pendingSignature()
                                        ->count();
                                }
                            @endphp
                            @if($pendingContracts > 0)
                            <span class="badge badge-warning right">{{ $pendingContracts }}</span>
                            @endif
                        </p>
                    </a>
                </li>
                @endplatformFeature

                <!-- Invoices -->
                @platformFeature('invoices')
                @if(auth()->user()?->can('access admin panel'))
                    <li class="nav-item">
                        <a href="{{ route('admin.invoices.index') }}" class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Invoices &amp; Payments</p>
                        </a>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') || request()->routeIs('payments.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>
                                Invoices
                                @php
                                    $unpaidInvoices = 0;
                                    if (auth()->user()->client) {
                                        $unpaidInvoices = \App\Models\Invoice::where('client_id', auth()->user()->client_id)
                                            ->unpaid()
                                            ->count();
                                    }
                                @endphp
                                @if($unpaidInvoices > 0)
                                <span class="badge badge-danger right">{{ $unpaidInvoices }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                @endif
                @endplatformFeature

                <!-- Documents -->
                @platformFeature('documents')
                <li class="nav-item {{ request()->routeIs('documents.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-folder-open"></i>
                        <p>
                            Documents
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>My Documents</p>
                            </a>
                        </li>
                        @if(auth()->user()?->client_id || auth()->user()?->can('access admin panel'))
                            <li class="nav-item">
                                <a href="{{ route('documents.smart-browser') }}" class="nav-link {{ request()->routeIs('documents.smart-browser') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Smart Browser</p>
                                </a>
                            </li>
                        @endif
                        @can('access admin panel')
                        <li class="nav-item">
                            <a href="{{ route('documents.templates') }}" class="nav-link {{ request()->routeIs('documents.templates') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Templates</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endplatformFeature

                {{-- ============================================= --}}
                {{-- CLIENT SECTION (Client users only) --}}
                {{-- ============================================= --}}
                @if(auth()->user()->isClient())
                <li class="nav-header">CLIENT</li>

                <li class="nav-item">
                    <a href="{{ route('client.projects') }}" class="nav-link {{ request()->routeIs('client.projects') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-project-diagram"></i>
                        <p>Projects</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('client.onboarding') }}" class="nav-link {{ request()->routeIs('client.onboarding') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list-check"></i>
                        <p>Onboarding</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('client.messaging') }}" class="nav-link {{ request()->routeIs('client.messaging') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Messages</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('client.meetings') }}" class="nav-link {{ request()->routeIs('client.meetings') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar"></i>
                        <p>Meetings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('client.knowledge-base') }}" class="nav-link {{ request()->routeIs('client.knowledge-base') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Knowledge Base</p>
                    </a>
                </li>

                {{-- ============================================= --}}
                {{-- MARKETING SECTION (Client marketing tools) --}}
                {{-- ============================================= --}}
                <li class="nav-header">MARKETING</li>

                <!-- SEO Dashboard -->
                <li class="nav-item">
                    <a href="{{ route('client.seo') }}" class="nav-link {{ request()->routeIs('client.seo') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-search-dollar"></i>
                        <p>SEO Dashboard</p>
                    </a>
                </li>

                <!-- Campaigns -->
                <li class="nav-item {{ request()->routeIs('client.campaigns*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('client.campaigns*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-rocket"></i>
                        <p>
                            Campaigns
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('client.campaigns') }}" class="nav-link {{ request()->routeIs('client.campaigns') && !request()->routeIs('client.campaigns.manage') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('client.campaigns.manage') }}" class="nav-link {{ request()->routeIs('client.campaigns.manage') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Campaigns</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Brand Monitoring (feature gated) -->
                @if(auth()->user()->client && auth()->user()->client->hasFeature('brand_monitoring'))
                <li class="nav-item">
                    <a href="{{ route('client.brand-monitoring.my-mentions') }}" class="nav-link {{ request()->routeIs('client.brand-monitoring.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        <p>Brand Monitor</p>
                    </a>
                </li>
                @endif

                <!-- Social Media Management -->
                <li class="nav-item {{ request()->routeIs('social.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('social.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-share-alt"></i>
                        <p>
                            Social Media
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('social.accounts') }}" class="nav-link {{ request()->routeIs('social.accounts') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Accounts</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('social.pending-approvals') }}" class="nav-link {{ request()->routeIs('social.pending-approvals') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Approvals
                                    @php
                                        $pendingPosts = 0;
                                        if (auth()->user()->client) {
                                            $pendingPosts = \App\Models\ContentCalendarItem::forClient(auth()->user()->client_id)
                                                ->pendingApproval()
                                                ->count();
                                        }
                                    @endphp
                                    @if($pendingPosts > 0)
                                    <span class="badge badge-warning right">{{ $pendingPosts }}</span>
                                    @endif
                                </p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- ============================================= --}}
                {{-- REPORTS SECTION (Client analytics) --}}
                {{-- ============================================= --}}
                <li class="nav-header">REPORTS</li>

                <li class="nav-item">
                    <a href="{{ route('client.analytics') }}" class="nav-link {{ request()->routeIs('client.analytics') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>Analytics</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('client.reports') }}" class="nav-link {{ request()->routeIs('client.reports') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('client.reports.archive') }}" class="nav-link {{ request()->routeIs('client.reports.archive') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-archive"></i>
                        <p>Report Archive</p>
                    </a>
                </li>

                {{-- ============================================= --}}
                {{-- STORAGE SECTION (Client cloud storage) --}}
                {{-- ============================================= --}}
                @php($navUser = auth()->user())
                @if($navUser?->client_id)
                <li class="nav-header">STORAGE</li>

                <li class="nav-item {{ request()->routeIs('storage.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('storage.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cloud"></i>
                        <p>
                            Cloud Storage
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('storage.dashboard') }}" class="nav-link {{ request()->routeIs('storage.dashboard') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('storage.browser') }}" class="nav-link {{ request()->routeIs('storage.browser') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>File Browser</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('storage.conflicts') }}" class="nav-link {{ request()->routeIs('storage.conflicts') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Conflicts</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('storage.settings') }}" class="nav-link {{ request()->routeIs('storage.settings') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                @endif

                {{-- ============================================= --}}
                {{-- ADMIN SECTION --}}
                {{-- ============================================= --}}
                @can('access admin panel')
                <li class="nav-header">ADMIN</li>

                <li class="nav-item">
                    <a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Clients</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') && !request()->routeIs('admin.users.permissions') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Users</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.permissions') }}" class="nav-link {{ request()->routeIs('admin.users.permissions') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key"></i>
                        <p>Permissions</p>
                    </a>
                </li>

                @platformFeature('messaging')
                <li class="nav-item">
                    <a href="{{ route('admin.messages') }}" class="nav-link {{ request()->routeIs('admin.messages') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Messages</p>
                    </a>
                </li>
                @endplatformFeature

                @platformFeature('meetings')
                <li class="nav-item">
                    <a href="{{ route('admin.meetings') }}" class="nav-link {{ request()->routeIs('admin.meetings') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Meetings</p>
                    </a>
                </li>
                @endplatformFeature

                @platformFeature('reporting')
                <li class="nav-item">
                    <a href="{{ route('admin.reports.dashboard') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Reporting</p>
                    </a>
                </li>
                @endplatformFeature

                @platformFeature('projects')
                <li class="nav-item {{ request()->routeIs('admin.projects.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-stopwatch"></i>
                        <p>
                            Projects
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.projects.time') }}" class="nav-link {{ request()->routeIs('admin.projects.time') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Time Tracker</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.projects.time-approvals') }}" class="nav-link {{ request()->routeIs('admin.projects.time-approvals') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Time Approvals</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.projects.budgets') }}" class="nav-link {{ request()->routeIs('admin.projects.budgets') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Budgets</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.projects.board') }}" class="nav-link {{ request()->routeIs('admin.projects.board') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Task Board</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.projects.timeline') }}" class="nav-link {{ request()->routeIs('admin.projects.timeline') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Timeline</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.projects.workload') }}" class="nav-link {{ request()->routeIs('admin.projects.workload') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Team Workload</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endplatformFeature

                @platformFeature('proposals')
                <li class="nav-item {{ request()->routeIs('admin.proposals.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.proposals.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-signature"></i>
                        <p>
                            Proposals
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.proposals.builder') }}" class="nav-link {{ request()->routeIs('admin.proposals.builder') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Builder</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endplatformFeature

                @platformFeature('contracts')
                @if(Route::has('admin.contracts.index'))
                <li class="nav-item {{ request()->routeIs('admin.contracts.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.contracts.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-contract"></i>
                        <p>
                            Contracts
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.contracts.index') }}" class="nav-link {{ request()->routeIs('admin.contracts.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>All Contracts</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.contracts.create') }}" class="nav-link {{ request()->routeIs('admin.contracts.create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>New Contract</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.contracts.generator') }}" class="nav-link {{ request()->routeIs('admin.contracts.generator') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>AI Generator</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                @endplatformFeature

                @platformFeature('feedback')
                <li class="nav-item {{ request()->routeIs('admin.feedback.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-poll"></i>
                        <p>
                            Feedback
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.feedback.surveys') }}" class="nav-link {{ request()->routeIs('admin.feedback.surveys') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Surveys</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.feedback.testimonials') }}" class="nav-link {{ request()->routeIs('admin.feedback.testimonials') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Testimonials</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endplatformFeature

                @platformFeature('account_management')
                <li class="nav-item {{ request()->routeIs('admin.account.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.account.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-handshake"></i>
                        <p>
                            Account Mgmt
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.account.health') }}" class="nav-link {{ request()->routeIs('admin.account.health') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Health</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.account.qbrs') }}" class="nav-link {{ request()->routeIs('admin.account.qbrs') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>QBRs</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.account.renewals') }}" class="nav-link {{ request()->routeIs('admin.account.renewals') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Renewals</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.account.upsells') }}" class="nav-link {{ request()->routeIs('admin.account.upsells') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Upsells</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endplatformFeature

                <!-- Staff How-To Guides (Admin/Staff only) -->
                @can('access admin panel')
                <li class="nav-item">
                    <a href="{{ route('admin.staff-guides') }}" class="nav-link {{ request()->routeIs('admin.staff-guides*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book-reader"></i>
                        <p>Staff Guides</p>
                    </a>
                </li>
                @endcan

                {{-- ============================================= --}}
                {{-- MARKETING SECTION (Admin marketing tools) --}}
                {{-- ============================================= --}}
                <li class="nav-header">MARKETING</li>

                @can('view_any_lead')
                <li class="nav-item">
                    <a href="{{ route('admin.marketing.leads') }}" class="nav-link {{ request()->routeIs('admin.marketing.leads') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-plus"></i>
                        <p>Leads</p>
                    </a>
                </li>
                @endcan

                <!-- Campaigns -->
                <li class="nav-item {{ request()->routeIs('admin.marketing.campaigns*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.marketing.campaigns*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-rocket"></i>
                        <p>
                            Campaigns
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.marketing.campaigns') }}" class="nav-link {{ request()->routeIs('admin.marketing.campaigns') && !request()->routeIs('admin.marketing.campaigns.manage') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Analytics</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.marketing.campaigns.manage') }}" class="nav-link {{ request()->routeIs('admin.marketing.campaigns.manage') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Campaigns</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Brand Monitoring -->
                <li class="nav-item {{ request()->routeIs('admin.brand-monitoring.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.brand-monitoring.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        <p>
                            Brand Monitor
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.brand-monitoring.dashboard') }}" class="nav-link {{ request()->routeIs('admin.brand-monitoring.dashboard') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.brand-monitoring.api-status') }}" class="nav-link {{ request()->routeIs('admin.brand-monitoring.api-status') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>API Status</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Social Media -->
                <li class="nav-item {{ request()->routeIs('admin.social.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.social.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-share-alt"></i>
                        <p>
                            Social Media
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.social.posts') }}" class="nav-link {{ request()->routeIs('admin.social.posts') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Posts</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.social.posts.create') }}" class="nav-link {{ request()->routeIs('admin.social.posts.create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Create Post</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.social.content-calendar') }}" class="nav-link {{ request()->routeIs('admin.social.content-calendar') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Calendar</p>
                            </a>
                        </li>
                    </ul>
                </li>

                @platformFeature('partners')
                <li class="nav-item {{ request()->routeIs('admin.partners') || request()->routeIs('admin.referrals') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.partners') || request()->routeIs('admin.referrals') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>
                            Partners
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.partners') }}" class="nav-link {{ request()->routeIs('admin.partners') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Partners</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.referrals') }}" class="nav-link {{ request()->routeIs('admin.referrals') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Referrals</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endplatformFeature

                {{-- ============================================= --}}
                {{-- SETTINGS SECTION (Admin system settings) --}}
                {{-- ============================================= --}}
                @can('manage settings')
                <li class="nav-header">SETTINGS</li>

                <li class="nav-item">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings') || request()->routeIs('admin.settings.index') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>System Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.settings.forms') }}" class="nav-link {{ request()->routeIs('admin.settings.forms*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-wpforms"></i>
                        <p>Form Templates</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.security.overview') }}" class="nav-link {{ request()->routeIs('admin.security.overview') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shield-alt"></i>
                        <p>Security Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.security.privacy-requests') }}" class="nav-link {{ request()->routeIs('admin.security.privacy-requests') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-shield"></i>
                        <p>Privacy Requests</p>
                    </a>
                </li>
                @platformFeature('storage_integrations')
                <li class="nav-item">
                    <a href="{{ route('admin.storage.overview') }}" class="nav-link {{ request()->routeIs('admin.storage.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-database"></i>
                        <p>Storage Overview</p>
                    </a>
                </li>
                @endplatformFeature
                @platformFeature('webhooks')
                <li class="nav-item">
                    <a href="{{ route('admin.webhooks.index') }}" class="nav-link {{ request()->routeIs('admin.webhooks.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-plug"></i>
                        <p>Webhooks</p>
                    </a>
                </li>
                @endplatformFeature
                @platformFeature('automation')
                <li class="nav-item">
                    <a href="{{ route('admin.automation.index') }}" class="nav-link {{ request()->routeIs('admin.automation.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-robot"></i>
                        <p>Automation</p>
                    </a>
                </li>
                @endplatformFeature
                @endcan
                @endcan

                {{-- ============================================= --}}
                {{-- ACCOUNT SECTION (User personal settings) --}}
                {{-- ============================================= --}}
                <li class="nav-header">ACCOUNT</li>

                <!-- Profile -->
                <li class="nav-item">
                    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-cog"></i>
                        <p>Profile Settings</p>
                    </a>
                </li>

                @if(auth()->user()->isClient())
                <li class="nav-item">
                    <a href="{{ route('client.notifications') }}" class="nav-link {{ request()->routeIs('client.notifications') ? 'active' : '' }}">
                        <i class="nav-icon far fa-bell"></i>
                        <p>Notifications</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('client.privacy') }}" class="nav-link {{ request()->routeIs('client.privacy') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-shield"></i>
                        <p>Privacy</p>
                    </a>
                </li>
                @endif

                <li class="nav-item">
                    <a href="{{ route('two-factor.setup') }}" class="nav-link {{ request()->routeIs('two-factor.setup') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shield-alt"></i>
                        <p>Two-factor (2FA)</p>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                        @csrf
                    </form>
                    <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Sign Out</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
