<?php

namespace App\Http\Livewire\Documents;

use App\Jobs\Ai\AnalyzeDocumentJob;
use App\Models\AiTask;
use App\Models\Document;
use Livewire\Component;

class DocumentAIAnalysis extends Component
{
    public Document $document;

    public string $forceType = ''; // contract|invoice|technical|unknown

    public string $language = 'en';

    public ?AiTask $latestTask = null;

    public ?array $analysis = null;

    public function mount(Document $document): void
    {
        $this->authorizeAccess($document);
        $this->document = $document->load('client');
        $this->refreshLatest();
    }

    protected function authorizeAccess(Document $doc): void
    {
        $user = auth()->user();
        if ($user && $user->isClient() && (int) $doc->client_id !== (int) $user->client_id) {
            abort(403);
        }
    }

    public function refreshLatest(): void
    {
        $needle = '"document_id":'.(int) $this->document->id;
        $this->latestTask = AiTask::query()
            ->where('task_type', 'analyze_document')
            ->where('input_data', 'like', '%'.$needle.'%')
            ->orderByDesc('id')
            ->first();

        $this->analysis = $this->latestTask && is_array($this->latestTask->output_data) ? $this->latestTask->output_data : null;
    }

    public function analyze(): void
    {
        AnalyzeDocumentJob::dispatch($this->document->id, [
            'force_type' => $this->forceType !== '' ? $this->forceType : null,
            'language' => $this->language ?: 'en',
        ]);

        session()->flash('success', 'Document analysis queued.');
        $this->refreshLatest();
    }

    public function render()
    {
        return view('livewire.documents.ai-analysis', [
            'document' => $this->document,
            'latestTask' => $this->latestTask,
            'analysis' => $this->analysis,
        ]);
    }
}
