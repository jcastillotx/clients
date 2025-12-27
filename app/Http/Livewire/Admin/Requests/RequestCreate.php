<?php

namespace App\Http\Livewire\Admin\Requests;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use App\Models\RequestComment;
use App\Models\User;
use App\Notifications\RequestActivityNotification;
use App\Notifications\RequestAssignedNotification;
use App\Services\ThumbnailService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class RequestCreate extends Component
{
    use WithFileUploads;

    public ?int $client_id = null;

    public string $title = '';

    public string $type = 'support';

    public string $priority = 'medium';

    public string $description = '';

    public ?int $assigned_to = null;

    public ?string $due_date = null; // YYYY-MM-DD

    public string $internal_note = '';

    public bool $notify_admins = true;

    public bool $notify_assignee = true;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $files = [];

    protected function rules(): array
    {
        $types = array_keys(config('client-portal.request_types', ['support' => 'Support']));
        $priorities = array_keys(config('client-portal.request_priorities', ['medium' => 'Medium']));
        $statuses = array_keys(config('client-portal.request_statuses', []));
        $maxKb = (int) config('client-portal.max_upload_size', 10240);

        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($types)],
            'priority' => ['required', Rule::in($priorities)],
            'description' => ['required', 'string'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'due_date' => ['nullable', 'date'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
            'files' => ['array'],
            'files.*' => ['file', "max:{$maxKb}", 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'notify_admins' => ['boolean'],
            'notify_assignee' => ['boolean'],
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function save()
    {
        $data = $this->validate();

        $user = auth()->user();
        $client = Client::query()->findOrFail((int) $data['client_id']);

        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'assigned_to' => $data['assigned_to'],
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'priority' => $data['priority'],
            'status' => 'pending',
            'due_date' => $data['due_date'],
        ]);

        if (trim((string) $data['internal_note']) !== '') {
            RequestComment::create([
                'request_id' => $request->id,
                'user_id' => $user->id,
                'comment' => trim((string) $data['internal_note']),
                'is_internal' => true,
            ]);
        }

        foreach ($this->files as $file) {
            $filename = (string) Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('requests/'.$request->id, $filename, 'attachments');

            $thumbnailPath = null;
            if (str_starts_with((string) $file->getMimeType(), 'image/')) {
                $thumb = app(ThumbnailService::class)->makeJpegThumbnailFromFile($file->getRealPath(), 640);
                if ($thumb) {
                    $thumbnailPath = 'requests/'.$request->id.'/thumbnails/'.(string) Str::uuid().'.jpg';
                    Storage::disk('attachments')->put($thumbnailPath, $thumb);
                }
            }

            RequestAttachment::create([
                'request_id' => $request->id,
                'uploaded_by' => $user->id,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        ActivityLog::log(
            "Admin created request: {$request->title}",
            $request,
            ['client_id' => $client->id],
            'created',
            'requests'
        );

        if ($this->notify_admins) {
            $recipients = User::query()->role(['super_admin', 'admin'])->get();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new RequestActivityNotification($request, 'created'));
            }
        }

        if ($this->notify_assignee && $request->assigned_to) {
            $assignee = User::query()->find($request->assigned_to);
            if ($assignee) {
                Notification::send($assignee, new RequestAssignedNotification($request->load('client')));
            }
        }

        session()->flash('success', 'Request created.');

        return redirect()->route('admin.requests.show', $request);
    }

    public function render()
    {
        $clientOptions = Client::query()->orderBy('company_name')->get(['id', 'company_name']);
        $staffOptions = User::query()->role(['super_admin', 'admin', 'staff'])->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.requests.create', [
            'clientOptions' => $clientOptions,
            'staffOptions' => $staffOptions,
            'types' => config('client-portal.request_types', []),
            'priorities' => config('client-portal.request_priorities', []),
        ])->layout('layouts.admin', ['title' => 'Create Request']);
    }
}
