<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Social Media Manager</h2>
            <p class="text-sm text-gray-600 mt-1">Schedule and manage posts across platforms</p>
        </div>
        <div class="flex space-x-3">
            <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                <button wire:click="$set('viewMode', 'calendar')" class="px-4 py-2 text-sm font-medium {{ $viewMode === 'calendar' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition-colors">
                    Calendar
                </button>
                <button wire:click="$set('viewMode', 'list')" class="px-4 py-2 text-sm font-medium {{ $viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition-colors">
                    List
                </button>
            </div>
            <button wire:click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Post
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Connected Accounts</h3>
        <div class="flex flex-wrap gap-3">
            @forelse($connectedAccounts as $account)
                <div class="flex items-center space-x-2 px-3 py-2 bg-green-50 border border-green-200 rounded-lg">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900 capitalize">{{ $account->platform }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-600">No connected accounts. Please connect your social media accounts first.</p>
            @endforelse
        </div>
    </div>

    @if($viewMode === 'calendar')
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <button wire:click="previousMonth" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y') }}
                </h3>
                <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-7 border-b border-gray-200">
                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                    <div class="p-3 text-center text-xs font-semibold text-gray-600 border-r border-gray-200 last:border-r-0">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            @foreach($calendarData as $week)
                <div class="grid grid-cols-7 border-b border-gray-200 last:border-b-0" style="min-height: 120px;">
                    @foreach($week as $day)
                        <div class="border-r border-gray-200 last:border-r-0 p-2 {{ $day && $day['date']->isToday() ? 'bg-blue-50' : '' }}">
                            @if($day)
                                <div class="text-sm font-medium text-gray-900 mb-2">{{ $day['date']->day }}</div>
                                <div class="space-y-1">
                                    @foreach($day['posts'] as $post)
                                        <div wire:click="editPost({{ $post->id }})" class="text-xs p-2 rounded cursor-pointer {{ $post->status === 'published' ? 'bg-green-100 text-green-800' : ($post->status === 'approved' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }} hover:opacity-80 transition-opacity">
                                            <div class="font-medium truncate">{{ $post->scheduled_for->format('g:i A') }}</div>
                                            <div class="truncate">{{ Str::limit($post->content, 30) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-200">
            @forelse($posts as $post)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full capitalize
                                    {{ $post->status === 'published' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $post->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $post->status === 'pending_approval' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $post->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $post->status === 'revision_requested' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ str_replace('_', ' ', $post->status) }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    {{ $post->scheduled_for->format('M d, Y g:i A') }}
                                </span>
                            </div>

                            <p class="text-base text-gray-900 mb-3">{{ $post->content }}</p>

                            <div class="flex items-center space-x-3">
                                <div class="flex space-x-2">
                                    @foreach($post->platforms ?? [] as $platform)
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded capitalize">
                                            {{ $platform }}
                                        </span>
                                    @endforeach
                                </div>
                                @if($post->media_urls)
                                    <span class="text-xs text-gray-500">{{ count($post->media_urls) }} media</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2 ml-4">
                            <button wire:click="editPost({{ $post->id }})" class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition-colors">
                                Edit
                            </button>
                            @if($post->status === 'approved' && !$post->published_at)
                                <button wire:click="publishNow({{ $post->id }})" class="px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded hover:bg-green-100 transition-colors">
                                    Publish Now
                                </button>
                            @endif
                            @if($post->status === 'pending_approval')
                                <button wire:click="approvePost({{ $post->id }})" class="px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded hover:bg-green-100 transition-colors">
                                    Approve
                                </button>
                            @endif
                            <button wire:click="deletePost({{ $post->id }})" class="px-3 py-1 text-xs font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition-colors">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No posts yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first post.</p>
                </div>
            @endforelse

            @if($posts->hasPages())
                <div class="p-6">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    @endif

    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $postId ? 'Edit Post' : 'Create New Post' }}</h3>
                </div>

                <form wire:submit.prevent="savePost" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea wire:model="content" rows="5" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Write your post content..."></textarea>
                        @error('content') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Platforms</label>
                        <div class="space-y-2">
                            @foreach($connectedAccounts as $account)
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" wire:model="platforms" value="{{ $account->platform }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700 capitalize">{{ $account->platform }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('platforms') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Scheduled Date</label>
                            <input type="date" wire:model="scheduledFor" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            @error('scheduledFor') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                            <input type="time" wire:model="scheduledTime" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            @error('scheduledTime') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select wire:model="status" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="draft">Draft</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="approved">Approved</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Media Files</label>
                        <input type="file" wire:model="mediaFiles" multiple class="w-full">
                        @error('mediaFiles.*') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hashtags</label>
                        <input type="text" wire:model="hashtags" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="#example #hashtags">
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            {{ $postId ? 'Update' : 'Create' }} Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
