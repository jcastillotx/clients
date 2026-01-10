<?php

namespace App\Http\Livewire\Admin\Tasks;

use App\Models\Client;
use App\Models\StaffTask;
use App\Models\StaffTaskBoard;
use App\Models\StaffTaskColumn;
use App\Models\StaffTaskLabel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskManagement extends Component
{
    public string $view = 'kanban'; // kanban, list, gantt, calendar
    public ?int $boardId = null;
    public string $search = '';
    public string $priorityFilter = '';
    public ?int $assigneeFilter = null;
    public ?int $clientFilter = null;
    public string $dateFilter = '';

    // Task creation/editing
    public bool $showTaskModal = false;
    public bool $showBoardModal = false;
    public bool $showColumnModal = false;
    public ?int $editingTaskId = null;
    public ?int $editingColumnId = null;

    // Task form fields
    public string $taskTitle = '';
    public string $taskDescription = '';
    public string $taskPriority = 'normal';
    public ?string $taskStartDate = null;
    public ?string $taskDueDate = null;
    public ?float $taskEstimatedHours = null;
    public ?int $taskColumnId = null;
    public ?int $taskClientId = null;
    public array $taskAssignees = [];
    public array $taskLabels = [];

    // Board form fields
    public string $boardName = '';
    public string $boardDescription = '';
    public string $boardColor = '#6366f1';

    // Column form fields
    public string $columnName = '';
    public string $columnColor = '#94a3b8';
    public ?int $columnWipLimit = null;
    public bool $columnIsDone = false;

    protected $listeners = [
        'taskMoved' => 'handleTaskMoved',
        'columnReordered' => 'handleColumnReordered',
        'taskReordered' => 'handleTaskReordered',
        'refreshBoard' => '$refresh',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        // Load default or first board
        $defaultBoard = StaffTaskBoard::active()->where('is_default', true)->first();
        if (!$defaultBoard) {
            $defaultBoard = StaffTaskBoard::active()->orderBy('sort_order')->first();
        }

        if ($defaultBoard) {
            $this->boardId = $defaultBoard->id;
        }
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function selectBoard(int $boardId): void
    {
        $this->boardId = $boardId;
        $this->resetFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->priorityFilter = '';
        $this->assigneeFilter = null;
        $this->clientFilter = null;
        $this->dateFilter = '';
    }

    // Board Management
    public function openBoardModal(): void
    {
        $this->resetBoardForm();
        $this->showBoardModal = true;
    }

    public function resetBoardForm(): void
    {
        $this->boardName = '';
        $this->boardDescription = '';
        $this->boardColor = '#6366f1';
    }

    public function saveBoard(): void
    {
        $this->validate([
            'boardName' => 'required|string|max:255',
            'boardDescription' => 'nullable|string|max:1000',
            'boardColor' => 'required|string|max:20',
        ]);

        $board = StaffTaskBoard::create([
            'name' => $this->boardName,
            'description' => $this->boardDescription,
            'color' => $this->boardColor,
            'created_by' => Auth::id(),
            'sort_order' => StaffTaskBoard::max('sort_order') + 1,
        ]);

        $this->boardId = $board->id;
        $this->showBoardModal = false;
        $this->resetBoardForm();

        session()->flash('success', 'Board created successfully.');
    }

    public function archiveBoard(int $boardId): void
    {
        $board = StaffTaskBoard::findOrFail($boardId);
        $board->update(['is_archived' => true]);

        if ($this->boardId === $boardId) {
            $this->boardId = StaffTaskBoard::active()->first()?->id;
        }

        session()->flash('success', 'Board archived.');
    }

    // Column Management
    public function openColumnModal(?int $columnId = null): void
    {
        $this->resetColumnForm();
        $this->editingColumnId = $columnId;

        if ($columnId) {
            $column = StaffTaskColumn::findOrFail($columnId);
            $this->columnName = $column->name;
            $this->columnColor = $column->color;
            $this->columnWipLimit = $column->wip_limit;
            $this->columnIsDone = $column->is_done_column;
        }

        $this->showColumnModal = true;
    }

    public function resetColumnForm(): void
    {
        $this->editingColumnId = null;
        $this->columnName = '';
        $this->columnColor = '#94a3b8';
        $this->columnWipLimit = null;
        $this->columnIsDone = false;
    }

    public function saveColumn(): void
    {
        $this->validate([
            'columnName' => 'required|string|max:255',
            'columnColor' => 'required|string|max:20',
            'columnWipLimit' => 'nullable|integer|min:1',
        ]);

        if ($this->editingColumnId) {
            $column = StaffTaskColumn::findOrFail($this->editingColumnId);
            $column->update([
                'name' => $this->columnName,
                'color' => $this->columnColor,
                'wip_limit' => $this->columnWipLimit,
                'is_done_column' => $this->columnIsDone,
            ]);
        } else {
            $maxOrder = StaffTaskColumn::where('board_id', $this->boardId)->max('sort_order') ?? -1;
            StaffTaskColumn::create([
                'board_id' => $this->boardId,
                'name' => $this->columnName,
                'color' => $this->columnColor,
                'wip_limit' => $this->columnWipLimit,
                'is_done_column' => $this->columnIsDone,
                'sort_order' => $maxOrder + 1,
            ]);
        }

        $this->showColumnModal = false;
        $this->resetColumnForm();

        session()->flash('success', 'Column saved successfully.');
    }

    public function deleteColumn(int $columnId): void
    {
        $column = StaffTaskColumn::findOrFail($columnId);

        if ($column->tasks()->count() > 0) {
            session()->flash('error', 'Cannot delete column with tasks. Move or delete tasks first.');
            return;
        }

        $column->delete();
        session()->flash('success', 'Column deleted.');
    }

    // Task Management
    public function openTaskModal(?int $taskId = null, ?int $columnId = null): void
    {
        $this->resetTaskForm();
        $this->editingTaskId = $taskId;
        $this->taskColumnId = $columnId;

        if ($taskId) {
            $task = StaffTask::with(['assignees', 'labels'])->findOrFail($taskId);
            $this->taskTitle = $task->title;
            $this->taskDescription = $task->description ?? '';
            $this->taskPriority = $task->priority;
            $this->taskStartDate = $task->start_date?->format('Y-m-d');
            $this->taskDueDate = $task->due_date?->format('Y-m-d');
            $this->taskEstimatedHours = $task->estimated_hours;
            $this->taskColumnId = $task->column_id;
            $this->taskClientId = $task->client_id;
            $this->taskAssignees = $task->assignees->pluck('id')->toArray();
            $this->taskLabels = $task->labels->pluck('id')->toArray();
        }

        $this->showTaskModal = true;
    }

    public function resetTaskForm(): void
    {
        $this->editingTaskId = null;
        $this->taskTitle = '';
        $this->taskDescription = '';
        $this->taskPriority = 'normal';
        $this->taskStartDate = null;
        $this->taskDueDate = null;
        $this->taskEstimatedHours = null;
        $this->taskColumnId = null;
        $this->taskClientId = null;
        $this->taskAssignees = [];
        $this->taskLabels = [];
    }

    public function saveTask(): void
    {
        $this->validate([
            'taskTitle' => 'required|string|max:255',
            'taskDescription' => 'nullable|string',
            'taskPriority' => 'required|in:low,normal,high,urgent',
            'taskStartDate' => 'nullable|date',
            'taskDueDate' => 'nullable|date|after_or_equal:taskStartDate',
            'taskEstimatedHours' => 'nullable|numeric|min:0',
            'taskColumnId' => 'required|exists:staff_task_columns,id',
        ]);

        $data = [
            'board_id' => $this->boardId,
            'column_id' => $this->taskColumnId,
            'title' => $this->taskTitle,
            'description' => $this->taskDescription ?: null,
            'priority' => $this->taskPriority,
            'start_date' => $this->taskStartDate,
            'due_date' => $this->taskDueDate,
            'estimated_hours' => $this->taskEstimatedHours,
            'client_id' => $this->taskClientId,
        ];

        if ($this->editingTaskId) {
            $task = StaffTask::findOrFail($this->editingTaskId);
            $task->update($data);
        } else {
            $data['created_by'] = Auth::id();
            $data['sort_order'] = StaffTask::where('column_id', $this->taskColumnId)->max('sort_order') + 1;
            $task = StaffTask::create($data);
        }

        // Sync assignees
        $task->assignees()->sync($this->taskAssignees);

        // Sync labels
        $task->labels()->sync($this->taskLabels);

        $this->showTaskModal = false;
        $this->resetTaskForm();

        session()->flash('success', 'Task saved successfully.');
    }

    public function deleteTask(int $taskId): void
    {
        StaffTask::findOrFail($taskId)->delete();
        session()->flash('success', 'Task deleted.');
    }

    public function handleTaskMoved(int $taskId, int $columnId, int $newIndex): void
    {
        $task = StaffTask::findOrFail($taskId);
        $oldColumnId = $task->column_id;
        $column = StaffTaskColumn::findOrFail($columnId);

        // Update task column
        $task->update(['column_id' => $columnId]);

        // Mark as completed if moved to done column
        if ($column->is_done_column && !$task->completed_at) {
            $task->markAsCompleted();
        } elseif (!$column->is_done_column && $task->completed_at) {
            $task->markAsIncomplete();
        }

        // Reorder tasks in the column
        $tasksInColumn = StaffTask::where('column_id', $columnId)
            ->where('id', '!=', $taskId)
            ->orderBy('sort_order')
            ->get();

        $order = 0;
        foreach ($tasksInColumn as $i => $t) {
            if ($i === $newIndex) {
                $task->update(['sort_order' => $order++]);
            }
            $t->update(['sort_order' => $order++]);
        }

        if ($newIndex >= $tasksInColumn->count()) {
            $task->update(['sort_order' => $order]);
        }
    }

    public function handleColumnReordered(array $columnIds): void
    {
        foreach ($columnIds as $index => $columnId) {
            StaffTaskColumn::where('id', $columnId)->update(['sort_order' => $index]);
        }
    }

    public function handleTaskReordered(int $columnId, array $taskIds): void
    {
        foreach ($taskIds as $index => $taskId) {
            StaffTask::where('id', $taskId)->update(['sort_order' => $index]);
        }
    }

    public function quickAddTask(int $columnId, string $title): void
    {
        if (trim($title) === '') {
            return;
        }

        StaffTask::create([
            'board_id' => $this->boardId,
            'column_id' => $columnId,
            'title' => trim($title),
            'priority' => 'normal',
            'created_by' => Auth::id(),
            'sort_order' => StaffTask::where('column_id', $columnId)->max('sort_order') + 1,
        ]);
    }

    public function getTasksProperty()
    {
        if (!$this->boardId) {
            return collect();
        }

        $query = StaffTask::query()
            ->where('board_id', $this->boardId)
            ->with(['assignees', 'labels', 'column', 'client', 'checklists'])
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->priorityFilter, fn($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->assigneeFilter, fn($q) => $q->assignedTo($this->assigneeFilter))
            ->when($this->clientFilter, fn($q) => $q->where('client_id', $this->clientFilter))
            ->when($this->dateFilter === 'overdue', fn($q) => $q->overdue())
            ->when($this->dateFilter === 'due_soon', fn($q) => $q->dueSoon(7))
            ->orderBy('sort_order');

        return $query->get();
    }

    public function render()
    {
        $boards = StaffTaskBoard::active()->orderBy('sort_order')->get();
        $currentBoard = $this->boardId ? StaffTaskBoard::with('columns.tasks')->find($this->boardId) : null;
        $columns = $currentBoard?->columns ?? collect();
        $staff = User::role(['admin', 'super_admin', 'staff'])->orderBy('name')->get();
        $clients = Client::orderBy('company_name')->get();
        $labels = $this->boardId
            ? StaffTaskLabel::forBoard($this->boardId)->get()
            : collect();

        // Get stats
        $stats = [
            'total' => $this->tasks->count(),
            'completed' => $this->tasks->whereNotNull('completed_at')->count(),
            'overdue' => $this->tasks->filter(fn($t) => $t->is_overdue)->count(),
            'due_soon' => $this->tasks->filter(fn($t) => $t->is_due_soon)->count(),
        ];

        return view('livewire.admin.tasks.management', [
            'boards' => $boards,
            'currentBoard' => $currentBoard,
            'columns' => $columns,
            'tasks' => $this->tasks,
            'staff' => $staff,
            'clients' => $clients,
            'labels' => $labels,
            'stats' => $stats,
        ])->layout('layouts.admin', ['title' => 'Task Management']);
    }
}
