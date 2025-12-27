<?php

namespace App\Livewire\Requests;

use App\Models\ActivityLog;
use App\Models\Request;
use App\Models\RequestAttachment;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateRequest extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $description = '';

    public string $type = 'support';

    public string $priority = 'medium';

    public ?string $due_date = null;

    public array $attachments = [];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20',
            'type' => 'required|in:'.implode(',', array_keys(config('client-portal.request_types'))),
            'priority' => 'required|in:'.implode(',', array_keys(config('client-portal.request_priorities'))),
            'due_date' => 'nullable|date|after:today',
            'attachments.*' => 'nullable|file|max:'.config('client-portal.max_upload_size'),
        ];
    }

    protected $messages = [
        'title.required' => 'Please enter a title for your request.',
        'description.required' => 'Please describe your request in detail.',
        'description.min' => 'Please provide more detail (at least 20 characters).',
    ];

    public function save()
    {
        $this->validate();

        $user = auth()->user();

        $request = Request::create([
            'client_id' => $user->client_id,
            'created_by' => $user->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'status' => 'pending',
        ]);

        // Handle attachments
        foreach ($this->attachments as $file) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs(
                'requests/'.$request->id,
                $filename,
                'attachments'
            );

            RequestAttachment::create([
                'request_id' => $request->id,
                'uploaded_by' => $user->id,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        ActivityLog::log(
            "Created new request: {$request->title}",
            $request,
            ['type' => $this->type, 'priority' => $this->priority],
            'created',
            'requests'
        );

        session()->flash('success', 'Your request has been submitted successfully!');

        return redirect()->route('requests.show', $request);
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function render()
    {
        return view('livewire.requests.create-request', [
            'types' => config('client-portal.request_types'),
            'priorities' => config('client-portal.request_priorities'),
        ]);
    }
}
