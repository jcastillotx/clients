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

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        if (! $user?->client_id) {
            abort(403);
        }

        $request = ServiceRequest::create([
            'client_id' => $user->client_id,
            'created_by' => $user->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => 'draft',
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

        ActivityLog::log(
            "Created new request: {$request->title}",
            $request,
            ['type' => $request->type, 'priority' => $request->priority],
            'created',
            'requests'
        );

        // Notify admins
        $recipients = User::query()->role(['super_admin', 'admin'])->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new RequestActivityNotification($request, 'created'));
        }

        session()->flash('success', 'Request saved as draft.');

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
