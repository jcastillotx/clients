<div x-data="{
    quickAddColumn: null,
    quickAddTitle: '',
    draggedTask: null,
    draggedColumn: null
}">
    <!-- Header -->
    <div class="flex flex-col gap-4 mb-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="text-sm text-slate-500">Team</div>
            <div class="text-2xl font-bold text-slate-900">Task Management</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="openBoardModal" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i class="fas fa-plus"></i> New Board
            </button>
            <button wire:click="openTaskModal(null, {{ $columns->first()?->id }})" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" @if(!$boardId) disabled @endif>
                <i class="fas fa-plus"></i> New Task
            </button>
        </div>
    </div>

    <!-- Board Tabs & View Toggle -->
    <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <!-- Board Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            @foreach($boards as $board)
                <button
                    wire:click="selectBoard({{ $board->id }})"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition {{ $boardId === $board->id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >
                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $board->color }}"></span>
                    {{ $board->name }}
                </button>
            @endforeach
            @if($boards->isEmpty())
                <span class="text-sm text-slate-500">No boards yet. Create one to get started.</span>
            @endif
        </div>

        <!-- View Toggle -->
        <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-1">
            <button wire:click="setView('kanban')" class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition {{ $view === 'kanban' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fas fa-columns"></i> Kanban
            </button>
            <button wire:click="setView('list')" class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition {{ $view === 'list' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fas fa-list"></i> List
            </button>
            <button wire:click="setView('gantt')" class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition {{ $view === 'gantt' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fas fa-chart-gantt"></i> Gantt
            </button>
            <button wire:click="setView('calendar')" class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition {{ $view === 'calendar' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fas fa-calendar"></i> Calendar
            </button>
        </div>
    </div>

    @if($boardId)
        <!-- Stats Cards -->
        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</div>
                <div class="text-xs font-medium text-slate-500">Total Tasks</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="text-2xl font-bold text-emerald-700">{{ $stats['completed'] }}</div>
                <div class="text-xs font-medium text-emerald-600">Completed</div>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="text-2xl font-bold text-red-700">{{ $stats['overdue'] }}</div>
                <div class="text-xs font-medium text-red-600">Overdue</div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="text-2xl font-bold text-amber-700">{{ $stats['due_soon'] }}</div>
                <div class="text-xs font-medium text-amber-600">Due Soon</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Search</label>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tasks..." class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Priority</label>
                    <select wire:model.live="priorityFilter" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="">All priorities</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Assignee</label>
                    <select wire:model.live="assigneeFilter" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="">All assignees</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Client</label>
                    <select wire:model.live="clientFilter" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="">All clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Due Date</label>
                    <select wire:model.live="dateFilter" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="">All dates</option>
                        <option value="overdue">Overdue</option>
                        <option value="due_soon">Due within 7 days</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- View Content -->
        @if($view === 'kanban')
            @include('livewire.admin.tasks.partials.kanban-view')
        @elseif($view === 'list')
            @include('livewire.admin.tasks.partials.list-view')
        @elseif($view === 'gantt')
            @include('livewire.admin.tasks.partials.gantt-view')
        @elseif($view === 'calendar')
            @include('livewire.admin.tasks.partials.calendar-view')
        @endif
    @else
        <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white p-12 text-center">
            <div class="mb-4 text-6xl text-slate-300">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">No Board Selected</h3>
            <p class="mb-4 text-sm text-slate-500">Create a new board to start managing tasks.</p>
            <button wire:click="openBoardModal" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                <i class="fas fa-plus"></i> Create Board
            </button>
        </div>
    @endif

    <!-- Board Modal -->
    @if($showBoardModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('showBoardModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-slate-900">Create New Board</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Board Name</label>
                        <input wire:model="boardName" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="e.g., Development Tasks">
                        @error('boardName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Description</label>
                        <textarea wire:model="boardDescription" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="Optional description..."></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Color</label>
                        <input wire:model="boardColor" type="color" class="mt-1 h-10 w-full rounded-lg border border-slate-300 p-1">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showBoardModal', false)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button wire:click="saveBoard" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Create Board</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Column Modal -->
    @if($showColumnModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('showColumnModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-slate-900">{{ $editingColumnId ? 'Edit Column' : 'Add Column' }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Column Name</label>
                        <input wire:model="columnName" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="e.g., In Review">
                        @error('columnName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Color</label>
                        <input wire:model="columnColor" type="color" class="mt-1 h-10 w-full rounded-lg border border-slate-300 p-1">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">WIP Limit (optional)</label>
                        <input wire:model="columnWipLimit" type="number" min="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="Max tasks in column">
                    </div>
                    <div class="flex items-center gap-2">
                        <input wire:model="columnIsDone" type="checkbox" id="columnIsDone" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <label for="columnIsDone" class="text-sm font-medium text-slate-700">Mark tasks as completed when moved here</label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showColumnModal', false)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button wire:click="saveColumn" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">{{ $editingColumnId ? 'Update' : 'Add' }} Column</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Task Modal -->
    @if($showTaskModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4" wire:click.self="$set('showTaskModal', false)">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-slate-900">{{ $editingTaskId ? 'Edit Task' : 'Create Task' }}</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Title</label>
                        <input wire:model="taskTitle" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="Task title">
                        @error('taskTitle') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Description</label>
                        <textarea wire:model="taskDescription" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="Task description..."></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Column</label>
                        <select wire:model="taskColumnId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                            <option value="">Select column</option>
                            @foreach($columns as $column)
                                <option value="{{ $column->id }}">{{ $column->name }}</option>
                            @endforeach
                        </select>
                        @error('taskColumnId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Priority</label>
                        <select wire:model="taskPriority" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Start Date</label>
                        <input wire:model="taskStartDate" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Due Date</label>
                        <input wire:model="taskDueDate" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        @error('taskDueDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Estimated Hours</label>
                        <input wire:model="taskEstimatedHours" type="number" step="0.5" min="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="0">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Client (optional)</label>
                        <select wire:model="taskClientId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                            <option value="">No client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Assignees</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($staff as $member)
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-sm transition {{ in_array($member->id, $taskAssignees) ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                                    <input type="checkbox" wire:model="taskAssignees" value="{{ $member->id }}" class="sr-only">
                                    {{ $member->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-between">
                    <div>
                        @if($editingTaskId)
                            <button wire:click="deleteTask({{ $editingTaskId }})" wire:confirm="Are you sure you want to delete this task?" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="$set('showTaskModal', false)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button wire:click="saveTask" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">{{ $editingTaskId ? 'Update' : 'Create' }} Task</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', () => {
        initializeSortables();
    });

    initializeSortables();
});

function initializeSortables() {
    // Column sortable
    const columnsContainer = document.getElementById('kanban-columns');
    if (columnsContainer && !columnsContainer.sortableInitialized) {
        new Sortable(columnsContainer, {
            animation: 150,
            handle: '.column-drag-handle',
            onEnd: function(evt) {
                const columnIds = Array.from(columnsContainer.children).map(el => parseInt(el.dataset.columnId));
                Livewire.dispatch('columnReordered', { columnIds });
            }
        });
        columnsContainer.sortableInitialized = true;
    }

    // Task sortables within columns
    document.querySelectorAll('.task-list').forEach(list => {
        if (!list.sortableInitialized) {
            new Sortable(list, {
                group: 'tasks',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: function(evt) {
                    const taskId = parseInt(evt.item.dataset.taskId);
                    const columnId = parseInt(evt.to.dataset.columnId);
                    const newIndex = evt.newIndex;
                    Livewire.dispatch('taskMoved', { taskId, columnId, newIndex });
                }
            });
            list.sortableInitialized = true;
        }
    });
}
</script>
@endpush
