<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('admin.settings') }}" class="hover:text-slate-700">Settings</a>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            <a href="{{ route('admin.settings.forms') }}" class="hover:text-slate-700">Forms</a>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            <span class="text-slate-900">{{ $name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ $name }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $description }}</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" wire:click="resetToDefaults" wire:confirm="Are you sure you want to reset all fields to defaults? This will remove any custom fields."
                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                    Reset to Defaults
                </button>
                <button type="button" wire:click="toggleAddField"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Field
                </button>
            </div>
        </div>
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

    {{-- Add New Field Form --}}
    @if($showAddField)
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Add New Field</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Field Label *</label>
                        <input type="text" wire:model="newFieldLabel" placeholder="e.g., Company Size"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Field Key (auto-generated if empty)</label>
                        <input type="text" wire:model="newFieldKey" placeholder="e.g., company_size"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Field Type *</label>
                        <select wire:model="newFieldType"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                            @foreach($fieldTypes as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="newFieldRequired"
                                   class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            <span class="text-sm text-slate-700">Required field</span>
                        </label>
                    </div>
                </div>

                @if(in_array($newFieldType, ['select', 'multiselect']))
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Options (one per line)</label>
                        <textarea wire:model="newFieldOptions" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none font-mono"></textarea>
                    </div>
                @endif

                <div class="flex items-center gap-3">
                    <button type="button" wire:click="addField"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors">
                        Add Field
                    </button>
                    <button type="button" wire:click="toggleAddField"
                            class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Fields List --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="text-base font-semibold text-slate-900">Form Fields</h3>
            <p class="text-sm text-slate-500 mt-1">Drag to reorder or use the arrow buttons. Fields marked with a lock icon are baseline fields and cannot be deleted.</p>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($fields as $index => $field)
                @php
                    $isBaseline = $this->isBaseline($field['key'] ?? '');
                    $isEditing = $editingFieldIndex === $index;
                @endphp
                
                @if($isEditing)
                    {{-- Edit Mode --}}
                    <div class="p-6 bg-blue-50">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Field Label *</label>
                                <input type="text" wire:model="editFieldLabel"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Field Key</label>
                                <input type="text" value="{{ $field['key'] }}" disabled
                                       class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500 font-mono cursor-not-allowed">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Field Type</label>
                                @if($isBaseline)
                                    <input type="text" value="{{ $fieldTypes[$field['type'] ?? 'text'] ?? $field['type'] }}" disabled
                                           class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500 cursor-not-allowed">
                                @else
                                    <select wire:model="editFieldType"
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                                        @foreach($fieldTypes as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="flex items-center pt-6">
                                @if($isBaseline)
                                    <span class="inline-flex items-center gap-1.5 text-sm text-slate-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Required (baseline field)
                                    </span>
                                @else
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="editFieldRequired"
                                               class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                        <span class="text-sm text-slate-700">Required field</span>
                                    </label>
                                @endif
                            </div>
                        </div>

                        @if(in_array($editFieldType, ['select', 'multiselect']) || in_array($field['type'] ?? '', ['select', 'multiselect']))
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Options (one per line)</label>
                                <textarea wire:model="editFieldOptions" rows="4"
                                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none font-mono"></textarea>
                            </div>
                        @endif

                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="saveFieldEdit"
                                    class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors">
                                Save Changes
                            </button>
                            <button type="button" wire:click="cancelEdit"
                                    class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                @else
                    {{-- View Mode --}}
                    <div class="px-6 py-4 flex items-center gap-4 hover:bg-slate-50 transition-colors group">
                        {{-- Reorder Buttons --}}
                        <div class="flex flex-col gap-1">
                            <button type="button" wire:click="moveFieldUp({{ $index }})" @if($index === 0) disabled @endif
                                    class="p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button type="button" wire:click="moveFieldDown({{ $index }})" @if($index === count($fields) - 1) disabled @endif
                                    class="p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        {{-- Field Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-900">{{ $field['label'] ?? $field['key'] }}</span>
                                @if($isBaseline)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800" title="Baseline field - cannot be deleted">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Baseline
                                    </span>
                                @endif
                                @if($field['required'] ?? false)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Required
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-sm text-slate-500">
                                <span class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">{{ $field['key'] }}</span>
                                <span>{{ $fieldTypes[$field['type'] ?? 'text'] ?? $field['type'] }}</span>
                                @if(!empty($field['options']))
                                    <span>{{ count($field['options']) }} options</span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button" wire:click="editField({{ $index }})"
                                    class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            @if(!$isBaseline)
                                <button type="button" wire:click="deleteField({{ $index }})" wire:confirm="Are you sure you want to delete this field?"
                                        class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @else
                                <span class="p-2 text-slate-300 cursor-not-allowed" title="Cannot delete baseline field">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <div class="p-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-slate-500 mb-4">No fields defined yet.</p>
                    <button type="button" wire:click="toggleAddField"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add First Field
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-6">
        <a href="{{ route('admin.settings.forms') }}" 
           class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Form Templates
        </a>
    </div>
</div>
