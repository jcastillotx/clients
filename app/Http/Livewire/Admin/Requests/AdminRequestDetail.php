<?php

namespace App\Http\Livewire\Admin\Requests;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use App\Models\RequestComment;
use App\Models\RequestTimeEntry;
use App\Models\User;
use App\Notifications\RequestAssignedNotification;
use App\Services\ThumbnailService;
use App\Services\Projects\ProjectConversionService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class AdminRequestDetail extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'smart-reply-selected' => 'applySmartReply',
    ];

    public ServiceRequest $request;

    public string $tab = 'overview';
    public bool $showInternal = false;

    public ?int $assigned_to = null;
    public ?string $due_date = null;

    public string $newComment = '';
    public bool $newCommentInternal = false;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $files = [];

    public string $timeHours = '';
    public string $timeNote = '';
    public ?string $timeLoggedAt = null; // datetime-local string

    public function mount(ServiceRequest $request): void
    {
        $this->request = $request->load(['client', 'creator', 'assignee', 'attachments.uploader']);
        $this->assigned_to = $this->request->assigned_to;
        $this->due_date = $this->request->due_date?->format('Y-m-d');
    }

    protected function rules(): array
    {
        $maxKb = (int) config('client-portal.max_upload_size', 10240);
        return [
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'due_date' => ['nullable', 'date'],
            'newComment' => ['nullable', 'string', 'max:5000'],
            'newCommentInternal' => ['boolean'],
            'files' => ['array'],
            'files.*' => ['file', "max:{$maxKb}", 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'timeHours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'timeNote' => ['nullable', 'string', 'max:5000'],
            'timeLoggedAt' => ['nullable', 'string'],
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'showInternal'], true)) return;
        $this->validateOnly($property);
    }

    public function saveAssignment(): void
    {
        $this->validateOnly('assigned_to');
        $this->validateOnly('due_date');

        $before = (int) ($this->request->assigned_to ?? 0);
        $this->request->update([
            'assigned_to' => $this->assigned_to,
            'due_date' => $this->due_date ?: null,
        ]);
        $this->request->refresh()->load('assignee', 'client');

        ActivityLog::log(
            "Updated assignment: {$this->request->title}",
            $this->request,
            ['assigned_to' => $this->assigned_to, 'due_date' => $this->due_date],
            'assigned',
            'requests'
        );

        if ($this->assigned_to && $this->assigned_to !== $before) {
            $staff = User::query()->find($this->assigned_to);
            if ($staff) {
                Notification::send($staff, new RequestAssignedNotification($this->request->load('client')));
            }
        }

        session()->flash('success', 'Assignment updated.');
    }

    public function setStatus(string $status): void
    {
        $allowed = array_keys(config('client-portal.request_statuses', []));
        if (!in_array($status, $allowed, true)) return;

        $updates = ['status' => $status];
        if ($status === 'in_progress' && !$this->request->started_at) {
            $updates['started_at'] = now();
        }
        if ($status === 'completed' && !$this->request->completed_at) {
            $updates['completed_at'] = now();
        }

        $this->request->update($updates);
        $this->request->refresh();

        ActivityLog::log(
            "Updated request status: {$this->request->title}",
            $this->request,
            ['status' => $status],
            'updated',
            'requests'
        );
    }

    public function convertToProject(ProjectConversionService $svc): void
    {
        $res = $svc->convert($this->request);
        if (($res['ok'] ?? false) === true) {
            session()->flash('success', 'Converted to project. Seeded tasks: ' . (int) ($res['seeded_tasks'] ?? 0));
        } else {
            session()->flash('error', 'Project conversion failed.');
        }
    }

    public function addComment(): void
    {
        $this->validateOnly('newComment');
        if (trim($this->newComment) === '') return;

        RequestComment::create([
            'request_id' => $this->request->id,
            'user_id' => auth()->id(),
            'comment' => trim($this->newComment),
            'is_internal' => (bool) $this->newCommentInternal,
        ]);

        $this->newComment = '';
        $this->newCommentInternal = false;

        ActivityLog::log(
            "Added comment to request: {$this->request->title}",
            $this->request,
            null,
            'commented',
            'requests'
        );
    }

    public function applySmartReply(string $text): void
    {
        $this->newComment = $text;
        $this->newCommentInternal = false;
    }

    public function uploadAttachments(): void
    {
        $this->validate();

        foreach ($this->files as $file) {
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('requests/' . $this->request->id, $filename, 'attachments');

            $thumbnailPath = null;
            if (str_starts_with((string) $file->getMimeType(), 'image/')) {
                $thumb = app(ThumbnailService::class)->makeJpegThumbnailFromFile($file->getRealPath(), 640);
                if ($thumb) {
                    $thumbnailPath = 'requests/' . $this->request->id . '/thumbnails/' . (string) Str::uuid() . '.jpg';
                    Storage::disk('attachments')->put($thumbnailPath, $thumb);
                }
            }

            RequestAttachment::create([
                'request_id' => $this->request->id,
                'uploaded_by' => auth()->id(),
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        $this->files = [];
        $this->request->load('attachments.uploader');

        session()->flash('success', 'Attachments uploaded.');
    }

    public function addTimeEntry(): void
    {
        $this->validateOnly('timeHours');
        if ($this->timeHours === '') return;

        $loggedAt = $this->timeLoggedAt ? \Carbon\Carbon::parse($this->timeLoggedAt) : now();

        RequestTimeEntry::create([
            'request_id' => $this->request->id,
            'user_id' => auth()->id(),
            'hours' => (float) $this->timeHours,
            'note' => trim($this->timeNote) ?: null,
            'logged_at' => $loggedAt,
        ]);

        // Keep request.actual_hours in sync (simple aggregate)
        $total = (float) RequestTimeEntry::query()->where('request_id', $this->request->id)->sum('hours');
        $this->request->update(['actual_hours' => $total]);

        $this->timeHours = '';
        $this->timeNote = '';
        $this->timeLoggedAt = null;

        session()->flash('success', 'Time entry added.');
    }

    public function render()
    {
        $comments = RequestComment::query()
            ->where('request_id', $this->request->id)
            ->when(!$this->showInternal, fn ($q) => $q->where('is_internal', false))
            ->with('user')
            ->latest()
            ->get();

        $internalNotes = RequestComment::query()
            ->where('request_id', $this->request->id)
            ->where('is_internal', true)
            ->with('user')
            ->latest()
            ->get();

        $timeEntries = RequestTimeEntry::query()
            ->where('request_id', $this->request->id)
            ->with('user')
            ->orderByDesc('logged_at')
            ->limit(50)
            ->get();

        $relatedDocuments = Document::query()
            ->where('client_id', $this->request->client_id)
            ->latest()
            ->limit(10)
            ->get();

        $staffOptions = User::query()->role(['super_admin', 'admin', 'staff'])->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.requests.detail', [
            'request' => $this->request->loadMissing(['client', 'creator', 'assignee', 'attachments.uploader']),
            'statusLabels' => config('client-portal.request_statuses', []),
            'comments' => $comments,
            'internalNotes' => $internalNotes,
            'timeEntries' => $timeEntries,
            'staffOptions' => $staffOptions,
            'relatedDocuments' => $relatedDocuments,
        ])->layout('layouts.admin', ['title' => 'Request #' . $this->request->id]);
    }
}

