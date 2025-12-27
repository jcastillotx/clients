<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Project Estimation</div>
            <div class="text-xl font-semibold text-slate-900">Get a Project Estimate</div>
        </div>
        @if($estimate)
            <button wire:click="resetForm" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Start New Estimate
            </button>
        @endif
    </div>

    <!-- Workload Status Banner -->
    <div class="rounded-xl border {{ $workloadSummary['status'] === 'high' ? 'border-amber-200 bg-amber-50' : ($workloadSummary['status'] === 'moderate' ? 'border-blue-200 bg-blue-50' : 'border-emerald-200 bg-emerald-50') }} p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                @if($workloadSummary['status'] === 'high')
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @elseif($workloadSummary['status'] === 'moderate')
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @endif
            </div>
            <div>
                <p class="text-sm font-medium {{ $workloadSummary['status'] === 'high' ? 'text-amber-800' : ($workloadSummary['status'] === 'moderate' ? 'text-blue-800' : 'text-emerald-800') }}">
                    Current Availability
                </p>
                <p class="text-sm {{ $workloadSummary['status'] === 'high' ? 'text-amber-700' : ($workloadSummary['status'] === 'moderate' ? 'text-blue-700' : 'text-emerald-700') }}">
                    {{ $workloadSummary['estimated_start'] }}
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error') || $error)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-sm text-rose-800">{{ session('error') ?? $error }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Form Section -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Project Details Card -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Project Details</h3>

                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Project Title *</label>
                        <input type="text" id="title" wire:model="title"
                               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('title') border-rose-500 @enderror"
                               placeholder="e.g., Company Website Redesign">
                        @error('title')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Project Description *</label>
                        <textarea id="description" wire:model="description" rows="5"
                                  class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('description') border-rose-500 @enderror"
                                  placeholder="Describe your project in detail. Include goals, features needed, target audience, and any specific requirements..."></textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-slate-500">The more detail you provide, the more accurate your estimate will be.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="project_type" class="block text-sm font-medium text-slate-700 mb-1">Project Type *</label>
                            <select id="project_type" wire:model.live="project_type"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                @foreach($projectTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="complexity" class="block text-sm font-medium text-slate-700 mb-1">Complexity *</label>
                            <select id="complexity" wire:model.live="complexity"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                @foreach($complexityLevels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="priority" class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
                            <select id="priority" wire:model="priority"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <option value="low">Low - Flexible timeline</option>
                                <option value="medium">Medium - Standard timeline</option>
                                <option value="high">High - Expedited</option>
                                <option value="urgent">Urgent - ASAP</option>
                            </select>
                        </div>

                        <div>
                            <label for="budget_range" class="block text-sm font-medium text-slate-700 mb-1">Budget Range</label>
                            <select id="budget_range" wire:model="budget_range"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                                <option value="">Select budget range...</option>
                                @foreach($budgetRanges as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="deadline" class="block text-sm font-medium text-slate-700 mb-1">Desired Deadline</label>
                        <input type="date" id="deadline" wire:model="deadline"
                               min="{{ now()->addDays(7)->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        @error('deadline')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="additional_notes" class="block text-sm font-medium text-slate-700 mb-1">Additional Notes</label>
                        <textarea id="additional_notes" wire:model="additional_notes" rows="3"
                                  class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                                  placeholder="Any other information that might help us understand your project better..."></textarea>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button wire:click="getQuickEstimate"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Quick Estimate
                    </button>

                    <button wire:click="generateEstimate"
                            wire:loading.attr="disabled"
                            wire:target="generateEstimate"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                        <span wire:loading.remove wire:target="generateEstimate">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Generate AI Estimate
                        </span>
                        <span wire:loading wire:target="generateEstimate" class="flex items-center">
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Analyzing...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Quick Estimate Result -->
            @if($showQuickEstimate && $quickEstimate)
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-6">
                    <h4 class="text-lg font-semibold text-blue-900 mb-3">Quick Estimate</h4>
                    <p class="text-sm text-blue-700 mb-4">{{ $quickEstimate['note'] }}</p>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-xs text-blue-600 uppercase tracking-wide">Low</div>
                            <div class="text-lg font-bold text-blue-900">{{ $quickEstimate['hours']['low'] }} hrs</div>
                            <div class="text-sm text-blue-700">${{ $quickEstimate['cost']['low'] }}</div>
                        </div>
                        <div class="text-center border-x border-blue-200">
                            <div class="text-xs text-blue-600 uppercase tracking-wide">Typical</div>
                            <div class="text-lg font-bold text-blue-900">{{ $quickEstimate['hours']['mid'] }} hrs</div>
                            <div class="text-sm text-blue-700">${{ $quickEstimate['cost']['mid'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-blue-600 uppercase tracking-wide">High</div>
                            <div class="text-lg font-bold text-blue-900">{{ $quickEstimate['hours']['high'] }} hrs</div>
                            <div class="text-sm text-blue-700">${{ $quickEstimate['cost']['high'] }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-blue-200">
                        <p class="text-sm text-blue-700">
                            <strong>Estimated Timeline:</strong>
                            {{ $quickEstimate['timeline']['weeks_to_complete'] }} weeks
                            (Start: {{ \Carbon\Carbon::parse($quickEstimate['timeline']['estimated_start'])->format('M j, Y') }})
                        </p>
                    </div>
                </div>
            @endif

            <!-- Full AI Estimate Result -->
            @if($estimate)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Detailed Estimate</h3>
                        <span class="text-xs text-slate-500">Generated {{ \Carbon\Carbon::parse($estimate['_meta']['generated_at'])->diffForHumans() }}</span>
                    </div>

                    @if(!empty($estimate['project_summary']))
                        <p class="text-sm text-slate-600 mb-6">{{ $estimate['project_summary'] }}</p>
                    @endif

                    <!-- Service Breakdown -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3">Service Breakdown</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200">
                                        <th class="text-left py-2 font-medium text-slate-600">Service</th>
                                        <th class="text-center py-2 font-medium text-slate-600">Hours (Low-High)</th>
                                        <th class="text-right py-2 font-medium text-slate-600">Cost Range</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estimate['services'] ?? [] as $service)
                                        <tr class="border-b border-slate-100 {{ $service['optional'] ?? false ? 'opacity-60' : '' }}">
                                            <td class="py-3">
                                                <div class="font-medium text-slate-900">
                                                    {{ $service['name'] }}
                                                    @if($service['optional'] ?? false)
                                                        <span class="text-xs text-slate-500">(Optional)</span>
                                                    @endif
                                                </div>
                                                @if(!empty($service['description']))
                                                    <div class="text-xs text-slate-500">{{ $service['description'] }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 text-center text-slate-600">
                                                {{ $service['hours']['low'] }} - {{ $service['hours']['high'] }}
                                            </td>
                                            <td class="py-3 text-right text-slate-600">
                                                ${{ number_format($service['cost']['low'], 0) }} - ${{ number_format($service['cost']['high'], 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="bg-slate-50 rounded-xl p-4 mb-6">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wide">Low Estimate</div>
                                <div class="text-xl font-bold text-slate-900">${{ number_format($estimate['totals']['with_markup']['low']['total'] ?? 0, 0) }}</div>
                                <div class="text-xs text-slate-500">{{ $estimate['totals']['hours']['low'] ?? 0 }} hours</div>
                            </div>
                            <div class="border-x border-slate-200">
                                <div class="text-xs text-slate-500 uppercase tracking-wide">Typical</div>
                                <div class="text-2xl font-bold text-slate-900">${{ number_format($estimate['totals']['with_markup']['mid']['total'] ?? 0, 0) }}</div>
                                <div class="text-xs text-slate-500">{{ $estimate['totals']['hours']['mid'] ?? 0 }} hours</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wide">High Estimate</div>
                                <div class="text-xl font-bold text-slate-900">${{ number_format($estimate['totals']['with_markup']['high']['total'] ?? 0, 0) }}</div>
                                <div class="text-xs text-slate-500">{{ $estimate['totals']['hours']['high'] ?? 0 }} hours</div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    @if(!empty($estimate['timeline']))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3">Estimated Timeline</h4>
                            <div class="bg-emerald-50 rounded-xl p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-emerald-900">
                                            Estimated Start: {{ \Carbon\Carbon::parse($estimate['timeline']['estimated_start'])->format('M j, Y') }}
                                        </div>
                                        <div class="text-sm text-emerald-700">
                                            Estimated Completion: {{ \Carbon\Carbon::parse($estimate['timeline']['estimated_completion'])->format('M j, Y') }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-emerald-900">{{ $estimate['timeline']['weeks_to_complete'] }}</div>
                                        <div class="text-xs text-emerald-600">weeks</div>
                                    </div>
                                </div>
                                @if(!empty($estimate['timeline']['factors']))
                                    <div class="mt-3 pt-3 border-t border-emerald-200">
                                        <div class="text-xs text-emerald-700">
                                            @foreach($estimate['timeline']['factors'] as $factor)
                                                <span class="inline-block bg-emerald-100 rounded px-2 py-0.5 mr-1 mb-1">{{ $factor }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Assumptions & Risks -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                        @if(!empty($estimate['assumptions']))
                            <div>
                                <h4 class="text-sm font-semibold text-slate-700 mb-2">Assumptions</h4>
                                <ul class="text-sm text-slate-600 space-y-1">
                                    @foreach($estimate['assumptions'] as $assumption)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                            {{ $assumption }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(!empty($estimate['risks']))
                            <div>
                                <h4 class="text-sm font-semibold text-slate-700 mb-2">Potential Risks</h4>
                                <ul class="text-sm text-slate-600 space-y-1">
                                    @foreach($estimate['risks'] as $risk)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            {{ $risk }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <!-- Submit as Request -->
                    <div class="border-t border-slate-200 pt-4">
                        <button wire:click="submitAsRequest"
                                class="w-full inline-flex items-center justify-center rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Submit as Project Request
                        </button>
                        <p class="text-xs text-slate-500 text-center mt-2">
                            This will create a formal request that our team will review and follow up on.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- How It Works -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">How It Works</h3>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-sm font-semibold text-slate-600">1</div>
                        <div>
                            <div class="font-medium text-slate-900">Describe Your Project</div>
                            <div class="text-sm text-slate-500">Tell us what you need built</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-sm font-semibold text-slate-600">2</div>
                        <div>
                            <div class="font-medium text-slate-900">AI Analysis</div>
                            <div class="text-sm text-slate-500">Our AI analyzes requirements & workload</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-sm font-semibold text-slate-600">3</div>
                        <div>
                            <div class="font-medium text-slate-900">Get Your Estimate</div>
                            <div class="text-sm text-slate-500">Receive detailed cost & timeline</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-sm font-semibold text-emerald-600">4</div>
                        <div>
                            <div class="font-medium text-slate-900">Submit Request</div>
                            <div class="text-sm text-slate-500">Convert to a formal project request</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Workload Info -->
            @if($estimate && !empty($estimate['workload_context']))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3">Team Capacity</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Team Utilization</span>
                            <span class="font-medium text-slate-900">{{ $estimate['workload_context']['team_utilization'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Available Capacity</span>
                            <span class="font-medium text-slate-900">{{ $estimate['workload_context']['available_capacity'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Current Backlog</span>
                            <span class="font-medium text-slate-900">{{ $estimate['workload_context']['current_backlog'] }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Disclaimer -->
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs text-slate-500">
                    <strong>Note:</strong> This is an AI-generated estimate based on the information provided. 
                    Final pricing may vary based on detailed requirements analysis. All estimates include 
                    our standard markup and contingency for project complexity.
                </p>
            </div>
        </div>
    </div>
</div>
