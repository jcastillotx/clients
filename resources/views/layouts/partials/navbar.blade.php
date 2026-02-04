<nav class="hidden lg:flex items-center justify-between h-16 bg-white border-b border-slate-200 px-6">
    <!-- Left navbar links -->
    <div class="flex items-center gap-4">
        <button class="lg:hidden text-slate-600 hover:text-slate-900" data-widget="pushmenu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="hidden sm:block">
            @php $isAdminArea = request()->routeIs('admin.*'); @endphp
            <a href="{{ $isAdminArea ? route('admin.dashboard') : route('dashboard') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">
                {{ $isAdminArea ? 'Admin Dashboard' : 'Dashboard' }}
            </a>
        </div>
    </div>

    <!-- Right navbar links -->
    <div class="flex items-center gap-4">
        <!-- Theme + Density -->
        <div class="hidden md:flex items-center gap-2">
            <button type="button" class="btn-theme-toggle" onclick="window.__toggleTheme && window.__toggleTheme()" title="Toggle Light/Dark Mode">
                <i class="fas fa-adjust"></i>
                <span>Light/Dark</span>
            </button>
            <button type="button" class="btn-density-toggle" onclick="window.__cycleDensity && window.__cycleDensity()" title="Cycle Padding Density">
                <i class="fas fa-compress-arrows-alt"></i>
                <span>Padding</span>
            </button>
        </div>

        <!-- Notifications Dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="relative text-slate-600 hover:text-slate-900">
                <i class="far fa-bell text-lg"></i>
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
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-500 rounded-full">{{ $pendingCount + $unread }}</span>
                @endif
            </button>
            <div x-show="open" @click.away="open = false"
                class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
                <div class="px-4 py-3 border-b border-slate-200">
                    <span class="font-semibold text-slate-900">Notifications</span>
                </div>
                @if($unread > 0)
                <a href="{{ route('client.notifications') }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <i class="fas fa-bell mr-2"></i> {{ $unread }} unread notification(s)
                        </div>
                        <span class="text-xs text-slate-500">Open</span>
                    </div>
                </a>
                <div class="border-t border-slate-200"></div>
                @endif
                @if($pendingCount > 0)
                <a href="{{ route('invoices.index') }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <i class="fas fa-file-invoice mr-2"></i> {{ $pendingCount }} pending invoice(s)
                        </div>
                        <span class="text-xs text-slate-500">View</span>
                    </div>
                </a>
                @else
                <div class="px-4 py-3 text-sm text-slate-500">No new notifications</div>
                @endif
            </div>
        </div>

        <!-- User Dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 text-sm">
                @php $u = auth()->user(); $photo = $u?->profilePhotoUrl(); @endphp
                @if($photo)
                    <img src="{{ $photo }}" alt="Profile photo" class="w-8 h-8 rounded-full object-cover">
                @else
                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center">
                        <i class="far fa-user text-slate-600 text-sm"></i>
                    </div>
                @endif
                <span class="hidden md:inline text-slate-700">{{ $u->name }}</span>
            </button>
            <div x-show="open" @click.away="open = false"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <i class="fas fa-user-cog mr-2"></i> Profile Settings
                </a>
                <div class="border-t border-slate-200 my-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
