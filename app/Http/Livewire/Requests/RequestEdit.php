<?php

namespace App\Http\Livewire\Requests;

use App\Models\ActivityLog;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use App\Notifications\RequestActivityNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class RequestEdit extends Component
{
    use WithFileUploads;

    public ServiceRequest $request;

    public string $title = '';
    public string $type = '';
    public string $priority = '';
    public string $description = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $files = [];

    /** @var array<int, int> */
    public array $removedAttachmentIds = [];

    protected function rules(): array
    {
        $types = implode(',', array_keys(config('client-portal.request_types', ['support' => 'Support'])));
        $priorities = implode(',', array_keys(config('client-portal.request_priorities', ['medium' => 'Medium'])));

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . $types],
            'priority' => ['required', 'in:' . $priorities],
            'description' => ['required', 'string'],
            'files' => ['array'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function mount(ServiceRequest $request): void
    {
        $this->authorizeClientAccess($request);

        if (!in_array($request->status, ['draft', 'pending'], true)) {
            abort(403);
        }

        $this->request = $request->load(['attachments.uploader']);

        $this->title = $this->request->title;
        $this->type = $this->request->type;
        $this->priority = $this->request->priority;
        $this->description = $this->request->description;
    }

    public function removeExistingAttachment(int $attachmentId): void
    {
        $attachment = RequestAttachment::query()
            ->where('request_id', $this->request->id)
            ->findOrFail($attachmentId);

        $this->removedAttachmentIds[] = $attachment->id;
        $this->removedAttachmentIds = array_values(array_unique($this->removedAttachmentIds));

        // Update in-memory list so UI updates immediately
        $this->request->setRelation(
            'attachments',
            $this->request->attachments->reject(fn ($a) => $a->id === $attachment->id)->values()
        );
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        $this->authorizeClientAccess($this->request);

        if (!in_array($this->request->status, ['draft', 'pending'], true)) {
            abort(403);
        }

        $this->request->update([
            'title' => $this->title,
            'type' => $this->type,
            'priority' => $this->priority,
            'description' => $this->description,
        ]);

        // Delete removed attachments (model hook deletes the file from storage)
        if (!empty($this->removedAttachmentIds)) {
            RequestAttachment::query()
                ->where('request_id', $this->request->id)
                ->whereIn('id', $this->removedAttachmentIds)
                ->get()
                ->each(fn ($a) => $a->delete());
        }

        // Add new attachments
        foreach ($this->files as $file) {
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('requests/' . $this->request->id, $filename, 'attachments');

            RequestAttachment::create([
                'request_id' => $this->request->id,
                'uploaded_by' => $user->id,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        ActivityLog::log(
            "Updated request: {$this->request->title}",
            $this->request,
            ['type' => $this->request->type, 'priority' => $this->request->priority],
            'updated',
            'requests'
        );

        $recipients = User::query()->role(['super_admin', 'admin'])->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new RequestActivityNotification($this->request, 'updated'));
        }

        session()->flash('success', 'Request updated.');

        return redirect()->route('requests.show', $this->request);
    }

    protected function authorizeClientAccess(ServiceRequest $request): void
    {
        $user = auth()->user();

        if ($user->isClient() && $request->client_id !== $user->client_id) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.requests.edit', [
            'types' => config('client-portal.request_types', []),
            'priorities' => config('client-portal.request_priorities', []),
        ]);
    }
}

