<?php

namespace App\Http\Livewire\Documents;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentUploadedNotification;
use App\Services\ThumbnailService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $category = 'misc';

    public ?int $clientId = null;

    public $file;

    protected function rules(): array
    {
        $categories = implode(',', array_keys(config('client-portal.document_categories', [])));
        $maxKb = (int) config('client-portal.max_document_upload_size', 51200);

        return [
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.$categories],
            'file' => ['required', 'file', "max:{$maxKb}", 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip'],
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function save(): void
    {
        $user = auth()->user();

        if (! $user->can('upload_document')) {
            abort(403);
        }

        // Clients can only upload to their own client_id.
        // Staff/Admin users must pick a client.
        $targetClientId = $user->client_id ?: $this->clientId;
        if (! $targetClientId) {
            $this->addError('clientId', 'Please select a client for this upload.');
            return;
        }
        if ($user->isClient() && (int) $targetClientId !== (int) $user->client_id) {
            abort(403);
        }

        $this->validate();

        $filename = (string) Str::uuid().'.'.$this->file->getClientOriginalExtension();
        $path = $this->file->storeAs(
            'clients/'.$targetClientId.'/documents',
            $filename,
            'documents'
        );

        $thumbnailPath = null;
        if (str_starts_with((string) $this->file->getMimeType(), 'image/')) {
            $thumb = app(ThumbnailService::class)->makeJpegThumbnailFromFile($this->file->getRealPath(), 640);
            if ($thumb) {
                $thumbnailPath = 'clients/'.$targetClientId.'/documents/thumbnails/'.(string) Str::uuid().'.jpg';
                Storage::disk('documents')->put($thumbnailPath, $thumb);
            }
        }

        $document = Document::create([
            'client_id' => (int) $targetClientId,
            'uploaded_by' => $user->id,
            'title' => $this->title,
            'description' => null,
            'filename' => $filename,
            'original_filename' => $this->file->getClientOriginalName(),
            'file_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $this->file->getMimeType(),
            'file_size' => $this->file->getSize(),
            'category' => $this->category,
            'is_public' => false,
        ]);

        ActivityLog::log(
            "Uploaded document: {$document->title}",
            $document,
            ['category' => $document->category],
            'uploaded',
            'documents'
        );

        $recipients = User::query()->role(['super_admin', 'admin'])->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new DocumentUploadedNotification($document));
        }

        $this->reset(['title', 'category', 'file', 'clientId']);

        $this->dispatch('document-uploaded');
        session()->flash('success', 'Document uploaded successfully.');
    }

    public function render()
    {
        $user = auth()->user();
        $clients = [];

        // Staff/admin users need a client selector
        if (! $user->client_id) {
            $clients = \App\Models\Client::query()
                ->orderBy('company_name')
                ->pluck('company_name', 'id')
                ->toArray();
        }

        return view('livewire.documents.upload', [
            'categories' => config('client-portal.document_categories', []),
            'clients' => $clients,
            'showClientSelector' => ! $user->client_id,
        ]);
    }
}
