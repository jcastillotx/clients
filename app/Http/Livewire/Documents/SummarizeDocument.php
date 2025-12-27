<?php

namespace App\Http\Livewire\Documents;

use App\Models\Document;
use App\Services\AI\DocumentAnalysisService;
use Livewire\Component;

class SummarizeDocument extends Component
{
    public Document $document;

    public string $language = 'en';

    public ?array $summary = null;

    public function mount(Document $document): void
    {
        $this->authorizeAccess($document);
        $this->document = $document;
    }

    protected function authorizeAccess(Document $doc): void
    {
        $user = auth()->user();
        if ($user && $user->isClient() && (int) $doc->client_id !== (int) $user->client_id) {
            abort(403);
        }
    }

    public function summarize(DocumentAnalysisService $svc): void
    {
        $this->summary = $svc->summarizeGeneric($this->document, '', ['text' => '', 'method' => 'none', 'warnings' => []], [
            'language' => $this->language ?: 'en',
        ]);
        session()->flash('success', 'Summary generated.');
    }

    public function render()
    {
        return view('livewire.documents.summarize-document', [
            'document' => $this->document,
            'summary' => $this->summary,
        ]);
    }
}
