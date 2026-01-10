<!-- Kanban Board -->
<div class="overflow-x-auto pb-4">
    <div id="kanban-columns" class="flex gap-4" style="min-width: max-content;">
        @foreach($columns as $column)
            <div class="flex w-80 flex-shrink-0 flex-col rounded-2xl border border-slate-200 bg-slate-50" data-column-id="{{ $column->id }}">
                <!-- Column Header -->
                <div class="flex items-center justify-between border-b border-slate-200 bg-white p-3 rounded-t-2xl">
                    <div class="flex items-center gap-2">
                        <div class="column-drag-handle cursor-move text-slate-400 hover:text-slate-600">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $column->color }}"></span>
                        <span class="font-semibold text-slate-900">{{ $column->name }}</span>
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">
                            {{ $tasks->where('column_id', $column->id)->count() }}
                            @if($column->wip_limit)
                                <span class="{{ $tasks->where('column_id', $column->id)->count() > $column->wip_limit ? 'text-red-600' : '' }}">/{{ $column->wip_limit }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button wire:click="openColumnModal({{ $column->id }})" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            <i class="fas fa-cog text-xs"></i>
                        </button>
                        <button wire:click="openTaskModal(null, {{ $column->id }})" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Tasks Container -->
                <div class="task-list flex-1 space-y-2 overflow-y-auto p-3" data-column-id="{{ $column->id }}" style="max-height: calc(100vh - 400px); min-height: 200px;">
                    @foreach($tasks->where('column_id', $column->id) as $task)
                        <div
                            data-task-id="{{ $task->id }}"
                            wire:click="openTaskModal({{ $task->id }})"
                            class="cursor-pointer rounded-xl border bg-white p-3 shadow-sm transition hover:shadow-md {{ $task->is_overdue ? 'border-red-300' : 'border-slate-200' }}"
                        >
                            <!-- Priority & Labels -->
                            <div class="mb-2 flex flex-wrap items-center gap-1">
                                <span class="inline-flex items-center rounded-full bg-{{ $task->priority_color }}-100 px-2 py-0.5 text-xs font-medium text-{{ $task->priority_color }}-700">
                                    {{ $task->priority_label }}
                                </span>
                                @foreach($task->labels->take(3) as $label)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium text-white" style="background-color: {{ $label->color }}">
                                        {{ $label->name }}
                                    </span>
                                @endforeach
                            </div>

                            <!-- Title -->
                            <h4 class="mb-2 font-medium text-slate-900 line-clamp-2">{{ $task->title }}</h4>

                            <!-- Description Preview -->
                            @if($task->description)
                                <p class="mb-2 text-xs text-slate-500 line-clamp-2">{{ $task->description }}</p>
                            @endif

                            <!-- Meta Info -->
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                @if($task->due_date)
                                    <span class="{{ $task->is_overdue ? 'text-red-600 font-medium' : ($task->is_due_soon ? 'text-amber-600' : '') }}">
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ $task->due_date->format('M d') }}
                                    </span>
                                @endif
                                @if($task->estimated_hours)
                                    <span>
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $task->estimated_hours }}h
                                    </span>
                                @endif
                                @if($task->checklists->count() > 0)
                                    @php $progress = $task->checklist_progress; @endphp
                                    <span class="{{ $progress['completed'] === $progress['total'] ? 'text-emerald-600' : '' }}">
                                        <i class="fas fa-check-square mr-1"></i>
                                        {{ $progress['completed'] }}/{{ $progress['total'] }}
                                    </span>
                                @endif
                                @if($task->client)
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5">
                                        {{ Str::limit($task->client->company_name, 15) }}
                                    </span>
                                @endif
                            </div>

                            <!-- Assignees -->
                            @if($task->assignees->count() > 0)
                                <div class="mt-2 flex -space-x-2">
                                    @foreach($task->assignees->take(4) as $assignee)
                                        <div class="h-6 w-6 rounded-full bg-slate-300 ring-2 ring-white flex items-center justify-center text-[10px] font-medium text-slate-700" title="{{ $assignee->name }}">
                                            {{ strtoupper(substr($assignee->name, 0, 2)) }}
                                        </div>
                                    @endforeach
                                    @if($task->assignees->count() > 4)
                                        <div class="h-6 w-6 rounded-full bg-slate-200 ring-2 ring-white flex items-center justify-center text-[10px] font-medium text-slate-600">
                                            +{{ $task->assignees->count() - 4 }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if($tasks->where('column_id', $column->id)->count() === 0)
                        <div class="rounded-xl border-2 border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">
                            Drop tasks here
                        </div>
                    @endif
                </div>

                <!-- Quick Add -->
                <div class="border-t border-slate-200 bg-white p-3 rounded-b-2xl">
                    <div x-show="quickAddColumn === {{ $column->id }}" x-cloak>
                        <input
                            type="text"
                            x-model="quickAddTitle"
                            @keydown.enter="$wire.quickAddTask({{ $column->id }}, quickAddTitle); quickAddTitle = ''; quickAddColumn = null"
                            @keydown.escape="quickAddColumn = null; quickAddTitle = ''"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                            placeholder="Task title..."
                            x-ref="quickAddInput{{ $column->id }}"
                        >
                        <div class="mt-2 flex gap-2">
                            <button
                                @click="$wire.quickAddTask({{ $column->id }}, quickAddTitle); quickAddTitle = ''; quickAddColumn = null"
                                class="flex-1 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800"
                            >Add</button>
                            <button
                                @click="quickAddColumn = null; quickAddTitle = ''"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                            >Cancel</button>
                        </div>
                    </div>
                    <button
                        x-show="quickAddColumn !== {{ $column->id }}"
                        @click="quickAddColumn = {{ $column->id }}; $nextTick(() => $refs.quickAddInput{{ $column->id }}.focus())"
                        class="w-full rounded-lg border border-dashed border-slate-300 py-2 text-sm text-slate-500 hover:border-slate-400 hover:text-slate-700"
                    >
                        <i class="fas fa-plus mr-1"></i> Add task
                    </button>
                </div>
            </div>
        @endforeach

        <!-- Add Column Button -->
        <div class="flex w-80 flex-shrink-0 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-6">
            <button wire:click="openColumnModal" class="flex flex-col items-center gap-2 text-slate-400 hover:text-slate-600">
                <i class="fas fa-plus text-2xl"></i>
                <span class="text-sm font-medium">Add Column</span>
            </button>
        </div>
    </div>
</div>
