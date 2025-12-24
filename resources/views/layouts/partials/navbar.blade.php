<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Notifications Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                @php
                    $user = auth()->user();
                    $pendingCount = 0;
                    if ($user && $user->client) {
                        $pendingCount = \App\Models\Invoice::where('client_id', $user->client_id)
                            ->whereIn('status', ['sent', 'overdue'])
                            ->count();
                    }
                @endphp
                @if($pendingCount > 0)
                <span class="badge badge-warning navbar-badge">{{ $pendingCount }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">Notifications</span>
                <div class="dropdown-divider"></div>
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
                <i class="far fa-user"></i>
                <span class="d-none d-md-inline ml-1">{{ auth()->user()->name }}</span>
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
