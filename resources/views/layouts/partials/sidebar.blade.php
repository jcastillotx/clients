<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="brand-link">
        <img src="{{ asset('images/logo.png') }}" alt="Kre8iv Designs" class="brand-image img-circle elevation-3" style="opacity: .8" onerror="this.style.display='none'">
        <span class="brand-text font-weight-light">Client Portal</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <div class="img-circle elevation-2 bg-info d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <span class="text-white font-weight-bold">{{ auth()->user()->initials }}</span>
                </div>
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
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Service Requests -->
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

                <!-- Contracts -->
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

                <!-- Invoices -->
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

                <!-- Documents -->
                <li class="nav-item">
                    <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-folder-open"></i>
                        <p>Documents</p>
                    </a>
                </li>

                <!-- Storage -->
                <li class="nav-item {{ request()->routeIs('storage.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('storage.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cloud"></i>
                        <p>
                            Storage
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

                @can('view reports')
                <li class="nav-header">ADMIN</li>
                <li class="nav-item">
                    <a href="{{ route('admin.reports.dashboard') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Reporting</p>
                    </a>
                </li>

                @can('access admin panel')
                <li class="nav-item">
                    <a href="{{ route('admin.storage.overview') }}" class="nav-link {{ request()->routeIs('admin.storage.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-database"></i>
                        <p>Storage Overview</p>
                    </a>
                </li>
                @can('manage settings')
                <li class="nav-item">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>System Settings</p>
                    </a>
                </li>
                @endcan
                @endcan
                @endcan

                <li class="nav-header">ACCOUNT</li>

                <!-- Profile -->
                <li class="nav-item">
                    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-cog"></i>
                        <p>Profile Settings</p>
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
