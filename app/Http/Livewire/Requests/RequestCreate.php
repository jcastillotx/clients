<?php

namespace App\Http\Livewire\Requests;

use App\Models\ActivityLog;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use App\Notifications\RequestActivityNotification;
use App\Services\ThumbnailService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class RequestCreate extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $type = 'support';

    public string $priority = 'medium';

    public string $description = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $files = [];

    protected function rules(): array
    {
        $types = implode(',', array_keys(config('client-portal.request_types', ['support' => 'Support'])));
        $priorities = implode(',', array_keys(config('client-portal.request_priorities', ['medium' => 'Medium'])));
        $maxKb = (int) config('client-portal.max_upload_size', 10240);

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.$types],
            'priority' => ['required', 'in:'.$priorities],
            'description' => ['required', 'string'],
            'files' => ['array'],
            'files.*' => ['file', "max:{$maxKb}", 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    /**
     * Save request as draft
     */
    public function saveDraft()
    {
        return $this->saveRequest('draft', false);
    }

    /**
     * Submit request (pending status, notify admins)
     */
    public function submit()
    {
        return $this->saveRequest('pending', true);
    }

    /**
     * Legacy save method - defaults to draft
     */
    public function save()
    {
        return $this->saveDraft();
    }

    /**
     * Core save logic
     */
    protected function saveRequest(string $status, bool $notifyAdmins)
    {
        $this->validate();

        $user = auth()->user();
        
        if (! $user) {
            session()->flash('error', 'You must be logged in to create a request.');
            return;
        }
        
        if (! $user->client_id) {
            session()->flash('error', 'Your account is not associated with a client. Please contact support.');
            return;
        }

        $request = ServiceRequest::create([
            'client_id' => $user->client_id,
            'created_by' => $user->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $status,
        ]);

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

        $actionType = $status === 'draft' ? 'drafted' : 'created';
        ActivityLog::log(
            ($status === 'draft' ? 'Saved draft request: ' : 'Submitted request: ') . $request->title,
            $request,
            ['type' => $request->type, 'priority' => $request->priority, 'status' => $status],
            $actionType,
            'requests'
        );

        // Notify admins only when submitting (not drafts)
        if ($notifyAdmins) {
            $recipients = User::query()->role(['super_admin', 'admin'])->get();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new RequestActivityNotification($request, 'created'));
            }
        }

        $message = $status === 'draft' 
            ? 'Request saved as draft. You can edit and submit it later.'
            : 'Request submitted successfully! Our team will review it shortly.';
            
        session()->flash('success', $message);

        return redirect()->route('requests.show', $request);
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function render()
    {
        return view('livewire.requests.create', [
            'types' => config('client-portal.request_types', []),
            'priorities' => config('client-portal.request_priorities', []),
        ]);
    }
}
