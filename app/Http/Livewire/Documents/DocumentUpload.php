<?php

namespace App\Http\Livewire\Documents;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentUploadedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $category = 'misc';
    public $file;

    protected function rules(): array
    {
        $categories = implode(',', array_keys(config('client-portal.document_categories', [])));

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:' . $categories],
            // 50MB max (51200 KB)
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip'],
        ];
    }

    public function save(): void
    {
        $user = auth()->user();

        if (!$user->can('upload_document')) {
            abort(403);
        }

        if (!$user->client_id) {
            abort(403);
        }

        $this->validate();

        $filename = (string) Str::uuid() . '.' . $this->file->getClientOriginalExtension();
        $path = $this->file->storeAs(
            'clients/' . $user->client_id . '/documents',
            $filename,
            'documents'
        );

        $document = Document::create([
            'client_id' => $user->client_id,
            'uploaded_by' => $user->id,
            'title' => $this->title,
            'description' => null,
            'filename' => $filename,
            'original_filename' => $this->file->getClientOriginalName(),
            'file_path' => $path,
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

        $this->reset(['title', 'category', 'file']);

        $this->dispatch('document-uploaded');
        session()->flash('success', 'Document uploaded successfully.');
    }

    public function render()
    {
        return view('livewire.documents.upload', [
            'categories' => config('client-portal.document_categories', []),
        ]);
    }
}

