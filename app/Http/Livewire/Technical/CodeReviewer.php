<?php

namespace App\Http\Livewire\Technical;

use App\Services\AI\CodeReviewService;
use Livewire\Component;

class CodeReviewer extends Component
{
    public string $provider = 'claude';

    public string $model = '';

    /**
     * JSON array: [{ "path":"...", "language":"php", "content":"..." }, ...]
     */
    public string $codeFilesJson = '';

    public string $contextJson = '';

    /** @var array<string,mixed>|null */
    public ?array $result = null;

    /** @var array<string,mixed>|null */
    public ?array $docs = null;

    public function mount(): void
    {
        $this->provider = 'claude';
        $this->model = '';
        $this->codeFilesJson = json_encode([
            ['path' => 'example.php', 'language' => 'php', 'content' => "<?php\n\n// Paste code here\n"],
        ], JSON_PRETTY_PRINT);
        $this->contextJson = json_encode([
            'stack' => 'Laravel',
            'goals' => ['security', 'maintainability', 'performance'],
        ], JSON_PRETTY_PRINT);
    }

    public function review(CodeReviewService $svc): void
    {
        $files = json_decode($this->codeFilesJson, true);
        if (! is_array($files)) {
            session()->flash('error', 'Code files JSON is invalid.');

            return;
        }
        $ctx = json_decode($this->contextJson, true);
        if (! is_array($ctx)) {
            $ctx = [];
        }

        $this->result = $svc->reviewCode($files, [
            'provider' => $this->provider,
            'model' => trim($this->model) !== '' ? $this->model : null,
            'task_type' => 'code_review',
            'timeout' => 240,
            'context' => $ctx,
            'user_id' => auth()->id(),
            'user_query' => 'Code review',
        ]);
    }

    public function generateDocs(CodeReviewService $svc): void
    {
        $files = json_decode($this->codeFilesJson, true);
        if (! is_array($files)) {
            session()->flash('error', 'Code files JSON is invalid.');

            return;
        }
        $ctx = json_decode($this->contextJson, true);
        if (! is_array($ctx)) {
            $ctx = [];
        }

        $combined = '';
        foreach ($files as $f) {
            if (! is_array($f)) {
                continue;
            }
            $path = (string) ($f['path'] ?? 'unknown');
            $combined .= "\n\n// FILE: {$path}\n".(string) ($f['content'] ?? '');
        }

        $this->docs = $svc->generateDocumentation($combined, [
            'provider' => $this->provider,
            'model' => trim($this->model) !== '' ? $this->model : null,
            'task_type' => 'code_docs_generate',
            'timeout' => 240,
            'context' => $ctx,
            'user_id' => auth()->id(),
            'user_query' => 'Generate documentation',
        ]);
    }

    public function render()
    {
        return view('livewire.technical.code-reviewer');
    }
}
