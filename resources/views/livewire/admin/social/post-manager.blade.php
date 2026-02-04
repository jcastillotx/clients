<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <!-- Total Posts -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 transition-transform duration-300 group-hover:scale-110 dark:bg-slate-700">
                    <i class="fas fa-list text-xl text-slate-600 dark:text-slate-300"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $stats['total'] }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">Total Posts</p>
            </div>
        </div>

        <!-- Pending Approval -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 transition-transform duration-300 group-hover:scale-110 dark:bg-amber-900/20">
                    <i class="fas fa-clock text-xl text-amber-600 dark:text-amber-400"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $stats['pending_approval'] }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">Pending Approval</p>
            </div>
        </div>

        <!-- Approved -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 transition-transform duration-300 group-hover:scale-110 dark:bg-emerald-900/20">
                    <i class="fas fa-check text-xl text-emerald-600 dark:text-emerald-400"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $stats['approved'] }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">Approved</p>
            </div>
        </div>

        <!-- Scheduled -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 transition-transform duration-300 group-hover:scale-110 dark:bg-blue-900/20">
                    <i class="fas fa-calendar text-xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $stats['scheduled'] }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">Scheduled</p>
            </div>
        </div>

        <!-- Published -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 transition-transform duration-300 group-hover:scale-110 dark:bg-indigo-900/20">
                    <i class="fas fa-paper-plane text-xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $stats['published'] }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">Published</p>
            </div>
        </div>

        <!-- Drafts -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 transition-transform duration-300 group-hover:scale-110 dark:bg-slate-700">
                    <i class="fas fa-file text-xl text-slate-600 dark:text-slate-300"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $stats['draft'] }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">Drafts</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="flex items-center text-lg font-semibold text-slate-900 dark:text-white">
                    <i class="fas fa-stream mr-3 text-slate-600 dark:text-slate-400"></i>
                    Social Media Posts
                </h3>
                <a href="{{ route('admin.social.posts.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-primary/90 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                    <i class="fas fa-plus"></i>
                    <span>Create New Post</span>
                </a>
            </div>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="ml-auto text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="ml-auto text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <!-- Filters -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="col-span-1">
                    <input wire:model.debounce.300ms="search" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20 dark:border-slate-600 dark:bg-slate-700 dark:text-white" placeholder="Search posts...">
                </div>
                <div class="col-span-1">
                    <select wire:model="selectedClient" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1">
                    <select wire:model="selectedPlatform" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                        <option value="">All Platforms</option>
                        @foreach($platforms as $platform)
                            <option value="{{ $platform }}">{{ ucfirst($platform) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1">
                    <select wire:model="selectedStatus" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1">
                    <button wire:click="clearFilters" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                        <i class="fas fa-times"></i>
                        <span>Clear Filters</span>
                    </button>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 5%;">Platform</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 20%;">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 15%;">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 25%;">Content Preview</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 10%;">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 10%;">Scheduled</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 10%;">Created By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400" style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($posts as $post)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <i class="{{ $post->platform_icon }} text-2xl text-slate-600 dark:text-slate-400"></i>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $post->title }}</div>
                                    @if($post->campaign_tag)
                                        <span class="mt-1 inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ $post->campaign_tag }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                    {{ $post->client->company_name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="max-w-xs truncate text-sm text-slate-600 dark:text-slate-400">
                                        {{ Str::limit($post->content_text, 100) }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold 
                                        @if($post->status_color === 'success') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                        @elseif($post->status_color === 'warning') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                        @elseif($post->status_color === 'danger') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                        @elseif($post->status_color === 'primary') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif($post->status_color === 'info') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                                        @else bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $post->status)) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                    @if($post->scheduled_for)
                                        {{ $post->scheduled_for->format('M d, Y H:i') }}
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500">Not scheduled</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                    {{ $post->creator->name ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.social.posts.edit', $post->id) }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 p-2 text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" title="Edit">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <button wire:click="duplicatePost({{ $post->id }})" class="inline-flex items-center justify-center rounded-md bg-slate-600 p-2 text-white shadow-sm transition-colors hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2" title="Duplicate">
                                            <i class="fas fa-copy text-sm"></i>
                                        </button>
                                        @if(in_array($post->status, ['draft', 'failed']))
                                            <button wire:click="deletePost({{ $post->id }})"
                                                    onclick="return confirm('Are you sure you want to delete this post?')"
                                                    class="inline-flex items-center justify-center rounded-md bg-red-600 p-2 text-white shadow-sm transition-colors hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                                    title="Delete">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-500 dark:text-slate-400">
                                        <i class="fas fa-inbox mb-4 text-5xl text-slate-300 dark:text-slate-600"></i>
                                        <p class="text-base">No posts found. <a href="{{ route('admin.social.posts.create') }}" class="text-brand-primary hover:underline">Create your first post</a></p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-700">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</div>
