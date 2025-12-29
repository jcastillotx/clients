<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('admin.settings') }}" class="hover:text-slate-700">Settings</a>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            <span class="text-slate-900">Forms</span>
        </div>
        <h2 class="text-2xl font-semibold text-slate-900">Form Templates</h2>
        <p class="text-sm text-slate-500 mt-1">Customize the fields shown on forms throughout the platform. Baseline fields cannot be deleted.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4">
            <p class="text-sm text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
            <p class="text-sm text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $template)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900">{{ $template['name'] }}</h3>
                        @if($template['is_active'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-600 mb-4">{{ $template['description'] }}</p>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span>{{ $template['field_count'] }} fields</span>
                        </div>
                        
                        <a href="{{ route('admin.settings.forms.edit', $template['slug']) }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(empty($templates))
        <div class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-slate-500">No form templates found.</p>
        </div>
    @endif
</div>
