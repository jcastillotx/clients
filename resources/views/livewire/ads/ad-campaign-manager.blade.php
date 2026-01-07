<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Ad Campaign Manager</h2>
            <p class="text-sm text-gray-600 mt-1">Create and manage advertising campaigns across platforms</p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Campaign
        </button>
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
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Connected Ad Accounts</h3>
        <div class="flex flex-wrap gap-3">
            @forelse($adAccounts as $account)
                <div class="flex items-center space-x-2 px-3 py-2 border border-gray-200 rounded-lg">
                    <div class="h-3 w-3 rounded-full" style="background-color: {{ $account->platform_color }}"></div>
                    <span class="text-sm font-medium text-gray-900">{{ $account->platform_display_name }}</span>
                    <span class="text-xs text-gray-500">{{ $account->account_name }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-600">No connected ad accounts. Please connect your ad accounts first.</p>
            @endforelse
        </div>
    </div>

    <div class="flex items-center space-x-4">
        <select wire:model="statusFilter" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="all">All Status</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="paused">Paused</option>
            <option value="completed">Completed</option>
            <option value="archived">Archived</option>
        </select>

        <select wire:model="platformFilter" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="all">All Platforms</option>
            <option value="google_ads">Google Ads</option>
            <option value="facebook_ads">Facebook Ads</option>
            <option value="instagram_ads">Instagram Ads</option>
            <option value="linkedin_ads">LinkedIn Ads</option>
            <option value="twitter_ads">Twitter Ads</option>
        </select>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-200">
        @forelse($campaigns as $campaign)
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $campaign->name }}</h3>
                            <span class="px-2 py-1 text-xs font-medium rounded-full capitalize {{ $campaign->status_badge_class }}">
                                {{ $campaign->status }}
                            </span>
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded capitalize">
                                {{ str_replace('_', ' ', $campaign->objective) }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-4 text-sm text-gray-600 mb-3">
                            <div class="flex items-center space-x-1">
                                <div class="h-3 w-3 rounded-full" style="background-color: {{ $campaign->adAccount->platform_color }}"></div>
                                <span>{{ $campaign->adAccount->platform_display_name }}</span>
                            </div>
                            @if($campaign->daily_budget)
                                <span>Daily: ${{ number_format($campaign->daily_budget, 2) }}</span>
                            @endif
                            @if($campaign->lifetime_budget)
                                <span>Lifetime: ${{ number_format($campaign->lifetime_budget, 2) }}</span>
                            @endif
                            @if($campaign->start_date)
                                <span>{{ $campaign->start_date->format('M d, Y') }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-4 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Spend</div>
                                <div class="text-lg font-bold text-gray-900">${{ number_format($campaign->total_spend, 2) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Conversions</div>
                                <div class="text-lg font-bold text-gray-900">{{ number_format($campaign->total_conversions) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Created By</div>
                                <div class="text-sm text-gray-700">{{ $campaign->createdBy?->name ?? 'System' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Created</div>
                                <div class="text-sm text-gray-700">{{ $campaign->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col space-y-2 ml-4">
                        <button wire:click="editCampaign({{ $campaign->id }})" class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition-colors">
                            Edit
                        </button>
                        @if($campaign->status === 'active' || $campaign->status === 'paused')
                            <button wire:click="toggleCampaignStatus({{ $campaign->id }})" class="px-3 py-1 text-xs font-medium text-yellow-600 bg-yellow-50 rounded hover:bg-yellow-100 transition-colors">
                                {{ $campaign->status === 'active' ? 'Pause' : 'Resume' }}
                            </button>
                        @endif
                        <button wire:click="deleteCampaign({{ $campaign->id }})" onclick="return confirm('Are you sure?')" class="px-3 py-1 text-xs font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No campaigns yet</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first ad campaign.</p>
            </div>
        @endforelse

        @if($campaigns->hasPages())
            <div class="p-6">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $campaignId ? 'Edit Campaign' : 'Create New Campaign' }}</h3>
                </div>

                <form wire:submit.prevent="saveCampaign" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ad Account</label>
                        <select wire:model="adAccountId" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Select ad account</option>
                            @foreach($adAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->platform_display_name }} - {{ $account->account_name }}</option>
                            @endforeach
                        </select>
                        @error('adAccountId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Name</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Summer Sale 2025" required>
                        @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Objective</label>
                            <select wire:model="objective" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                                <option value="conversions">Conversions</option>
                                <option value="awareness">Brand Awareness</option>
                                <option value="consideration">Consideration</option>
                                <option value="traffic">Website Traffic</option>
                                <option value="engagement">Engagement</option>
                                <option value="app_installs">App Installs</option>
                                <option value="video_views">Video Views</option>
                                <option value="lead_generation">Lead Generation</option>
                                <option value="messages">Messages</option>
                                <option value="sales">Sales</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select wire:model="status" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Daily Budget ($)</label>
                            <input type="number" wire:model="dailyBudget" step="0.01" min="1" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="50.00">
                            @error('dailyBudget') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lifetime Budget ($)</label>
                            <input type="number" wire:model="lifetimeBudget" step="0.01" min="1" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="1000.00">
                            @error('lifetimeBudget') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" wire:model="startDate" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            @error('startDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date (Optional)</label>
                            <input type="date" wire:model="endDate" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            @error('endDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience (Description)</label>
                        <textarea wire:model="targetAudience" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Age 25-45, interested in fitness and wellness"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            {{ $campaignId ? 'Update' : 'Create' }} Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
