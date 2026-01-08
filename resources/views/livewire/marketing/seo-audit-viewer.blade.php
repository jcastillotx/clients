<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">SEO Audit Results</h2>
            <p class="text-sm text-gray-600 mt-1">Comprehensive website analysis and recommendations</p>
        </div>
        <button wire:click="runNewAudit" {{ $runningAudit ? 'disabled' : '' }} class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            @if($runningAudit)
                <svg class="animate-spin h-5 w-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Running Audit...
            @else
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Run New Audit
            @endif
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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Audit History</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($audits as $auditItem)
                        <button wire:click="selectAudit({{ $auditItem->id }})" class="w-full text-left p-4 hover:bg-gray-50 transition-colors {{ $selectedAuditId == $auditItem->id ? 'bg-blue-50' : '' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">{{ $auditItem->created_at->format('M d, Y') }}</span>
                                <span class="text-lg font-bold {{ $this->getScoreColorClass($auditItem->overall_score) }}">
                                    {{ $auditItem->overall_score }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-600">
                                {{ $auditItem->pages_crawled }} pages
                            </div>
                        </button>
                    @empty
                        <div class="p-4 text-center text-sm text-gray-500">
                            No audits yet
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            @if($audit)
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-5xl font-bold {{ $this->getScoreColorClass($audit->overall_score) }} mb-2">
                                    {{ $audit->overall_score }}
                                </div>
                                <p class="text-sm text-gray-600">Overall Score</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold {{ $this->getScoreColorClass($audit->seo_score) }} mb-2">
                                    {{ $audit->seo_score }}
                                </div>
                                <p class="text-sm text-gray-600">SEO Score</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold {{ $this->getScoreColorClass($audit->performance_score) }} mb-2">
                                    {{ $audit->performance_score }}
                                </div>
                                <p class="text-sm text-gray-600">Performance</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold {{ $this->getScoreColorClass($audit->accessibility_score) }} mb-2">
                                    {{ $audit->accessibility_score }}
                                </div>
                                <p class="text-sm text-gray-600">Accessibility</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold {{ $this->getScoreColorClass($audit->mobile_score) }} mb-2">
                                    {{ $audit->mobile_score }}
                                </div>
                                <p class="text-sm text-gray-600">Mobile</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="border-b border-gray-200">
                            <nav class="flex -mb-px">
                                <button wire:click="$set('activeTab', 'overview')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'overview' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                                    Overview
                                </button>
                                <button wire:click="$set('activeTab', 'issues')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'issues' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                                    Issues ({{ $audit->issues()->count() }})
                                </button>
                                <button wire:click="$set('activeTab', 'pages')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'pages' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                                    Pages ({{ $audit->pages_crawled }})
                                </button>
                                <button wire:click="$set('activeTab', 'insights')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'insights' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                                    AI Insights
                                </button>
                            </nav>
                        </div>

                        <div class="p-6">
                            @if($activeTab === 'overview')
                                <div class="space-y-6">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <div class="text-2xl font-bold text-gray-900">{{ $audit->pages_crawled }}</div>
                                                <div class="text-sm text-gray-600">Pages Crawled</div>
                                            </div>
                                            <div class="bg-red-50 rounded-lg p-4">
                                                <div class="text-2xl font-bold text-red-600">{{ $issuesBySeverity['critical'] ?? 0 }}</div>
                                                <div class="text-sm text-gray-600">Critical Issues</div>
                                            </div>
                                            <div class="bg-yellow-50 rounded-lg p-4">
                                                <div class="text-2xl font-bold text-yellow-600">{{ $issuesBySeverity['warning'] ?? 0 }}</div>
                                                <div class="text-sm text-gray-600">Warnings</div>
                                            </div>
                                            <div class="bg-blue-50 rounded-lg p-4">
                                                <div class="text-2xl font-bold text-blue-600">{{ $issuesBySeverity['info'] ?? 0 }}</div>
                                                <div class="text-sm text-gray-600">Info</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Issues by Category</h3>
                                        <div class="space-y-3">
                                            @foreach($issuesByCategory as $category => $count)
                                                <div>
                                                    <div class="flex justify-between items-center mb-1">
                                                        <span class="text-sm font-medium text-gray-700 capitalize">{{ str_replace('_', ' ', $category) }}</span>
                                                        <span class="text-sm text-gray-600">{{ $count }} issues</span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                                        <div class="bg-red-600 h-2 rounded-full" style="width: {{ ($count / array_sum($issuesByCategory)) * 100 }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if($audit->meta && isset($audit->meta['core_web_vitals']))
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Core Web Vitals</h3>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                                    <div class="text-sm text-gray-600 mb-2">LCP (Largest Contentful Paint)</div>
                                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($audit->meta['core_web_vitals']['lcp'] ?? 0, 2) }}s</div>
                                                </div>
                                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                                    <div class="text-sm text-gray-600 mb-2">FID (First Input Delay)</div>
                                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($audit->meta['core_web_vitals']['fid'] ?? 0, 2) }}ms</div>
                                                </div>
                                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                                    <div class="text-sm text-gray-600 mb-2">CLS (Cumulative Layout Shift)</div>
                                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($audit->meta['core_web_vitals']['cls'] ?? 0, 3) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @elseif($activeTab === 'issues')
                                <div class="space-y-4">
                                    @foreach($audit->issues()->orderBy('severity')->get() as $issue)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-3 mb-2">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                                            {{ $issue->severity === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                                            {{ $issue->severity === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                            {{ $issue->severity === 'info' ? 'bg-blue-100 text-blue-800' : '' }}">
                                                            {{ ucfirst($issue->severity) }}
                                                        </span>
                                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full capitalize">
                                                            {{ str_replace('_', ' ', $issue->category) }}
                                                        </span>
                                                    </div>
                                                    <h4 class="text-base font-semibold text-gray-900 mb-1">{{ $issue->title }}</h4>
                                                    <p class="text-sm text-gray-600 mb-2">{{ $issue->description }}</p>
                                                    @if($issue->affected_url)
                                                        <a href="{{ $issue->affected_url }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                                                            {{ Str::limit($issue->affected_url, 80) }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($issue->recommendation)
                                                <div class="mt-3 pt-3 border-t border-gray-200">
                                                    <div class="text-xs font-medium text-gray-700 mb-1">Recommendation:</div>
                                                    <div class="text-sm text-gray-600">{{ $issue->recommendation }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($activeTab === 'pages')
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Load Time</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($audit->pages as $page)
                                                <tr>
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        <a href="{{ $page->url }}" target="_blank" class="hover:text-blue-600">
                                                            {{ Str::limit($page->url, 60) }}
                                                        </a>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                                            {{ $page->status_code >= 200 && $page->status_code < 300 ? 'bg-green-100 text-green-800' : '' }}
                                                            {{ $page->status_code >= 300 && $page->status_code < 400 ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                            {{ $page->status_code >= 400 ? 'bg-red-100 text-red-800' : '' }}">
                                                            {{ $page->status_code }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-600">
                                                        {{ number_format($page->load_time, 2) }}s
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-600">
                                                        {{ $page->issue_count ?? 0 }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif($activeTab === 'insights')
                                <div class="prose max-w-none">
                                    @if($audit->ai_insights)
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4">AI-Powered Insights</h3>
                                            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $audit->ai_insights }}</div>
                                        </div>
                                    @else
                                        <div class="text-center py-12 text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                            <p>No AI insights available for this audit.</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Audit Selected</h3>
                    <p class="text-gray-600 mb-6">Run your first audit to get started with SEO analysis.</p>
                    <button wire:click="runNewAudit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Run First Audit
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
