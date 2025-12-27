<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class UploadDocument extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public string $category = 'other';
    public $file;

    protected function rules(): array
    {
        $maxSize = config('client-portal.max_upload_size');
        $allowedTypes = implode(',', config('client-portal.allowed_file_types'));

        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:' . implode(',', array_keys(config('client-portal.document_categories'))),
            'file' => "required|file|max:{$maxSize}|mimes:{$allowedTypes}",
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Please enter a title for the document.',
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file size must not exceed ' . (config('client-portal.max_upload_size') / 1024) . 'MB.',
        ];
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        $filename = Str::uuid() . '.' . $this->file->getClientOriginalExtension();
        
        $path = $this->file->storeAs(
            'clients/' . $user->client_id,
            $filename,
            'documents'
        );

        $document = Document::create([
            'client_id' => $user->client_id,
            'uploaded_by' => $user->id,
            'title' => $this->title,
            'description' => $this->description,
            'filename' => $filename,
            'original_filename' => $this->file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $this->file->getMimeType(),
            'file_size' => $this->file->getSize(),
            'category' => $this->category,
        ]);

        ActivityLog::log(
            "Uploaded document: {$document->title}",
            $document,
            ['category' => $this->category],
            'uploaded',
            'documents'
        );

        session()->flash('success', 'Document uploaded successfully!');

        $this->reset(['title', 'description', 'category', 'file']);
        $this->dispatch('document-uploaded');
    }

    public function render()
    {
        return view('livewire.documents.upload-document', [
            'categories' => config('client-portal.document_categories'),
        ]);
    }
}
