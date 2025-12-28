<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            @php $isAdminArea = request()->routeIs('admin.*'); @endphp
            <a href="{{ $isAdminArea ? route('admin.dashboard') : route('dashboard') }}" class="nav-link">
                {{ $isAdminArea ? 'Admin Dashboard' : 'Dashboard' }}
            </a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Theme + Density -->
        <li class="nav-item d-none d-md-flex align-items-center">
            <button type="button" class="btn-theme-toggle mr-2" onclick="window.__toggleTheme && window.__toggleTheme()" title="Toggle Light/Dark Mode">
                <i class="fas fa-adjust"></i>
                <span>Light/Dark</span>
            </button>
            <button type="button" class="btn-density-toggle" onclick="window.__cycleDensity && window.__cycleDensity()" title="Cycle Padding Density">
                <i class="fas fa-compress-arrows-alt"></i>
                <span>Padding</span>
            </button>
        </li>

        <!-- Notifications Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                @php
                    $user = auth()->user();
                    $pendingCount = 0;
                    $unread = 0;
                    if ($user && $user->client) {
                        $pendingCount = \App\Models\Invoice::where('client_id', $user->client_id)
                            ->whereIn('status', ['sent', 'overdue'])
                            ->count();
                        $unread = $user->unreadNotificationsCount();
                    }
                @endphp
                @if(($pendingCount + $unread) > 0)
                <span class="badge badge-warning navbar-badge">{{ $pendingCount + $unread }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">Notifications</span>
                <div class="dropdown-divider"></div>
                @if($unread > 0)
                <a href="{{ route('client.notifications') }}" class="dropdown-item">
                    <i class="fas fa-bell mr-2"></i> {{ $unread }} unread notification(s)
                    <span class="float-right text-muted text-sm">Open</span>
                </a>
                <div class="dropdown-divider"></div>
                @endif
                @if($pendingCount > 0)
                <a href="{{ route('invoices.index') }}" class="dropdown-item">
                    <i class="fas fa-file-invoice mr-2"></i> {{ $pendingCount }} pending invoice(s)
                    <span class="float-right text-muted text-sm">View</span>
                </a>
                @else
                <span class="dropdown-item text-muted">No new notifications</span>
                @endif
            </div>
        </li>

        <!-- User Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                @php $u = auth()->user(); $photo = $u?->profilePhotoUrl(); @endphp
                @if($photo)
                    <img src="{{ $photo }}" alt="Profile photo" class="img-circle elevation-1" style="width: 22px; height: 22px; object-fit: cover;">
                @else
                    <i class="far fa-user"></i>
                @endif
                <span class="d-none d-md-inline ml-1">{{ $u->name }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                    <i class="fas fa-user-cog mr-2"></i> Profile Settings
                </a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>
