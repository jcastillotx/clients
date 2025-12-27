<?php

namespace App\Http\Livewire\Projects;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskDetail extends Component
{
    use WithFileUploads;

    public Task $task;

    public string $title = '';

    public string $description = '';

    public string $status = 'todo';

    public string $priority = 'normal';

    public ?int $assignedTo = null;

    public ?string $startDate = null;

    public ?string $dueDate = null;

    public ?int $dependsOnTaskId = null;

    public string $estimatedHours = '';

    public string $newComment = '';

    public bool $newCommentInternal = true;

    public $upload;

    public function mount(Task $task): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $this->task = $task->load(['request', 'assignee', 'dependsOn']);

        $this->title = (string) $task->title;
        $this->description = (string) ($task->description ?? '');
        $this->status = (string) $task->status;
        $this->priority = (string) $task->priority;
        $this->assignedTo = $task->assigned_to;
        $this->startDate = $task->start_date?->toDateString();
        $this->dueDate = $task->due_date?->toDateString();
        $this->dependsOnTaskId = $task->depends_on_task_id;
        $this->estimatedHours = $task->estimated_hours !== null ? (string) $task->estimated_hours : '';
    }

    public function save(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        Validator::make([
            'title' => $this->title,
            'status' => $this->status,
            'priority' => $this->priority,
            'assignedTo' => $this->assignedTo,
            'startDate' => $this->startDate,
            'dueDate' => $this->dueDate,
            'dependsOnTaskId' => $this->dependsOnTaskId,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:todo,in_progress,blocked,done'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'assignedTo' => ['nullable', 'integer', 'exists:users,id'],
            'startDate' => ['nullable', 'date'],
            'dueDate' => ['nullable', 'date'],
            'dependsOnTaskId' => ['nullable', 'integer', 'exists:tasks,id'],
        ])->validate();

        if ($this->dependsOnTaskId && (int) $this->dependsOnTaskId === (int) $this->task->id) {
            abort(422, 'A task cannot depend on itself.');
        }

        $this->task->update([
            'title' => trim($this->title),
            'description' => trim($this->description) ?: null,
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->assignedTo ?: null,
            'start_date' => $this->startDate ?: null,
            'due_date' => $this->dueDate ?: null,
            'depends_on_task_id' => $this->dependsOnTaskId ?: null,
            'estimated_hours' => $this->estimatedHours !== '' ? (float) $this->estimatedHours : null,
        ]);

        $this->task->refresh();
        session()->flash('success', 'Task updated.');
    }

    public function addComment(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        Validator::make([
            'newComment' => $this->newComment,
        ], [
            'newComment' => ['required', 'string', 'max:5000'],
        ])->validate();

        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $u->id,
            'comment' => trim($this->newComment),
            'is_internal' => (bool) $this->newCommentInternal,
        ]);

        $this->newComment = '';
        $this->newCommentInternal = true;
        session()->flash('success', 'Comment added.');
    }

    public function uploadAttachment(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        Validator::make(['upload' => $this->upload], [
            'upload' => ['required', 'file', 'max:51200'],
        ])->validate();

        $file = $this->upload;
        $filename = (string) Str::uuid().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $path = $file->storeAs('tasks/'.$this->task->id, $filename, 'attachments');

        TaskAttachment::create([
            'task_id' => $this->task->id,
            'uploaded_by' => $u->id,
            'disk' => 'attachments',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => (int) $file->getSize(),
        ]);

        $this->reset('upload');
        session()->flash('success', 'Attachment uploaded.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $this->task->loadMissing(['request', 'assignee']);

        $assignees = User::query()->orderBy('name')->get(['id', 'name']);
        $otherTasks = Task::query()
            ->where('request_id', $this->task->request_id)
            ->where('id', '!=', $this->task->id)
            ->orderBy('id')
            ->get(['id', 'title']);

        $comments = TaskComment::query()->where('task_id', $this->task->id)->with('user')->latest('id')->limit(100)->get();
        $attachments = TaskAttachment::query()->where('task_id', $this->task->id)->with('uploader')->latest('id')->limit(100)->get();

        return view('livewire.projects.task-detail', compact('assignees', 'otherTasks', 'comments', 'attachments'));
    }
}
