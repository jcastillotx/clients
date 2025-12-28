<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900">Questionnaire Builder</h2>
        <p class="text-sm text-slate-500 mt-1">Create and manage onboarding questionnaires</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
            </svg>
            <h3 class="text-base font-semibold text-slate-900">Questionnaire Details</h3>
        </div>
        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Title</label>
                    <input type="text" wire:model.defer="title" placeholder="e.g., Brand Discovery Questionnaire" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Type</label>
                    <input type="text" wire:model.defer="questionnaireType" placeholder="e.g., intake, brand_discovery, custom" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                    <p class="mt-1.5 text-xs text-slate-500">Examples: intake, brand_discovery, content_brief, custom</p>
                </div>
            </div>

            <!-- Questions Section -->
            <div class="border-t border-slate-200 pt-5">
                <div class="flex items-center justify-between mb-4">
                    <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Questions</label>
                    <button type="button" wire:click="addQuestion" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Question
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($questions as $idx => $q)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Key</label>
                                    <input type="text" wire:model.defer="questions.{{ $idx }}.key" placeholder="field_key" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors font-mono">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Type</label>
                                    <select wire:model.defer="questions.{{ $idx }}.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                        <option value="text">Text</option>
                                        <option value="textarea">Textarea</option>
                                        <option value="select">Select</option>
                                        <option value="multiselect">Multi-select</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-6">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Label</label>
                                    <input type="text" wire:model.defer="questions.{{ $idx }}.label" placeholder="Question label" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-200">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.defer="questions.{{ $idx }}.required" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                    <span class="text-sm text-slate-700">Required</span>
                                </label>
                                <button type="button" wire:click="removeQuestion({{ $idx }})" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Remove question">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Options for select/multiselect can be edited in the JSON field directly.</p>
                        </div>
                    @endforeach

                    @if(empty($questions))
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-slate-500">No questions yet. Click "Add Question" to get started.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="pt-4">
                <button type="button" wire:click="save" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                    </svg>
                    Save Questionnaire
                </button>
            </div>
        </div>
    </div>
</div>
