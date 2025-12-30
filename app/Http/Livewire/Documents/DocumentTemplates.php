<?php

namespace App\Http\Livewire\Documents;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\StorageConnection;
use App\Services\AI\AIProviderManager;
use App\Services\DocumentTemplateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class DocumentTemplates extends Component
{
    public string $name = '';

    public string $category = 'general';

    public string $body = '';

    public string $variables_csv = 'client_name, client_email, company_name';

    public ?int $editing_template_id = null;

    public string $ai_request_prompt = '';

    public bool $ai_request_loading = false;

    // generate
    public ?int $generate_client_id = null;

    public ?int $generate_template_id = null;

    public string $generate_destination = 'local'; // local|connection:{id}

    public string $generate_title = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        app(DocumentTemplateService::class)->seedDefaults();

        // This page is intentionally admin-only: it can generate documents for arbitrary clients.
        if (! ($user->can('manage documents') || $user->can('access admin panel'))) {
            redirect()
                ->route('documents.index')
                ->with('error', 'You do not have access to document templates.')
                ->send();
            return;
        }
    }

    public function saveTemplate(): void
    {
        Validator::make([
            'name' => $this->name,
            'category' => $this->category,
            'body' => $this->body,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:60'],
            'body' => ['required', 'string'],
        ])->validate();

        $vars = collect(explode(',', $this->variables_csv))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();

        if ($this->editing_template_id) {
            $template = DocumentTemplate::query()->findOrFail($this->editing_template_id);
            $template->update([
                'name' => $this->name,
                'category' => $this->category,
                'body' => $this->body,
                'variables' => $vars,
            ]);
            session()->flash('success', 'Template updated.');
        } else {
            DocumentTemplate::create([
                'name' => $this->name,
                'category' => $this->category,
                'body' => $this->body,
                'variables' => $vars,
                'created_by' => Auth::id(),
            ]);
            session()->flash('success', 'Template saved.');
        }

        $this->resetTemplateForm();
    }

    public function editTemplate(int $templateId): void
    {
        $template = DocumentTemplate::query()->findOrFail($templateId);
        $this->editing_template_id = $template->id;
        $this->name = (string) $template->name;
        $this->category = (string) $template->category;
        $this->body = (string) $template->body;
        $this->variables_csv = implode(', ', $template->variables ?? []);
    }

    public function cancelEdit(): void
    {
        $this->resetTemplateForm();
    }

    public function requestTemplateFromAi(AIProviderManager $providers): void
    {
        Validator::make([
            'prompt' => $this->ai_request_prompt,
        ], [
            'prompt' => ['required', 'string', 'min:10'],
        ])->validate();

        $this->ai_request_loading = true;

        try {
            $systemPrompt = 'You are a business document template generator. Return ONLY valid JSON without markdown or code fences. '
                .'Schema: { "name": string, "category": string, "variables": string[], "body": string }. '
                .'The body should be HTML with {{variable}} placeholders. Keep it concise and reusable.';
            $userPrompt = "Request: {$this->ai_request_prompt}";

            $response = $providers->withFallback('openai', function ($provider) use ($systemPrompt, $userPrompt) {
                return $provider->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ], [
                    'task_type' => 'document_template_request',
                    'timeout' => 120,
                ]);
            }, 'document_template_request');

            $text = (string) ($response['text'] ?? '');
            $data = $this->parseJsonFromText($text);

            if (! is_array($data) || empty($data)) {
                throw new \RuntimeException('AI did not return a valid JSON template.');
            }

            $this->name = (string) ($data['name'] ?? $this->name);
            $this->category = (string) ($data['category'] ?? $this->category);
            $this->body = (string) ($data['body'] ?? $this->body);
            $variables = $data['variables'] ?? [];
            $this->variables_csv = is_array($variables) ? implode(', ', $variables) : $this->variables_csv;
            $this->editing_template_id = null;

            session()->flash('success', 'AI draft loaded into the editor. Review and save.');
        } catch (\Throwable $e) {
            session()->flash('error', 'AI request failed: '.$e->getMessage());
        }

        $this->ai_request_loading = false;
    }

    public function generate(): void
    {
        Validator::make([
            'client' => $this->generate_client_id,
            'template' => $this->generate_template_id,
            'title' => $this->generate_title,
        ], [
            'client' => ['required', 'integer', 'exists:clients,id'],
            'template' => ['required', 'integer', 'exists:document_templates,id'],
            'title' => ['required', 'string', 'max:160'],
        ])->validate();

        $client = Client::query()->findOrFail($this->generate_client_id);
        $tpl = DocumentTemplate::query()->findOrFail($this->generate_template_id);

        $rendered = $this->renderTemplate($tpl->body ?? '', $client);

        $filename = Str::slug($this->generate_title).'.html';
        $path = 'generated/'.now()->format('Ymd_His').'_'.$filename;

        $providerDisk = null;
        $providerLabel = 'Local';

        if (str_starts_with($this->generate_destination, 'connection:')) {
            $id = (int) str_replace('connection:', '', $this->generate_destination);
            $conn = StorageConnection::query()
                ->where('client_id', $client->id)
                ->where('status', 'active')
                ->find($id);

            if (! $conn) {
                session()->flash('error', 'Selected storage connection is not available for this client.');
                return;
            }

            if (! $conn->disk) {
                session()->flash('error', 'Selected storage connection is missing a configured disk.');
                return;
            }

            $providerDisk = $conn->disk;
            $providerLabel = strtoupper($conn->provider);
        }

        // Always write a local copy for portal downloads (documents disk).
        Storage::disk('documents')->put($path, $rendered);
        // Optionally also write to the selected provider disk.
        if ($providerDisk && $providerDisk !== 'documents') {
            Storage::disk($providerDisk)->put($path, $rendered);
        }

        // Always create a local Document record for portal visibility, even if stored on provider disk.
        $doc = Document::create([
            'client_id' => $client->id,
            'uploaded_by' => Auth::id(),
            'title' => $this->generate_title,
            'description' => "Generated from template: {$tpl->name} ({$providerLabel})",
            'filename' => $filename,
            'original_filename' => $filename,
            'file_path' => $path,
            'mime_type' => 'text/html',
            'file_size' => strlen($rendered),
            'category' => 'template',
            'is_public' => false,
            'status' => 'draft',
            'current_version' => 1,
        ]);

        $savedTo = $providerDisk ? "'documents' + '{$providerDisk}'" : "'documents'";
        session()->flash('success', "Generated document #{$doc->id} and saved file to disk {$savedTo}.");
    }

    public function updatedGenerateClientId(): void
    {
        $this->generate_destination = 'local';
    }

    protected function resetTemplateForm(): void
    {
        $this->reset(['name', 'category', 'body', 'variables_csv', 'editing_template_id']);
    }

    protected function renderTemplate(string $body, Client $client): string
    {
        $vars = [
            'client_name' => $client->contact_name,
            'client_email' => $client->email,
            'company_name' => $client->company_name,
            'website' => $client->website,
        ];

        // Simple {{var}} replacement
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($vars) {
            $k = $m[1];

            return isset($vars[$k]) ? e((string) $vars[$k]) : $m[0];
        }, $body) ?? $body;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseJsonFromText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    public function render()
    {
        $templates = DocumentTemplate::query()->latest('id')->limit(50)->get();
        $clients = Client::query()->orderBy('company_name')->limit(200)->get();

        $connections = collect();
        if ($this->generate_client_id) {
            $connections = StorageConnection::query()
                ->where('client_id', $this->generate_client_id)
                ->where('status', 'active')
                ->orderByDesc('is_primary')
                ->get()
                ->map(fn ($c) => [
                    'value' => 'connection:'.$c->id,
                    'label' => "{$c->name} (".strtoupper($c->provider).')',
                ]);
        }

        return view('livewire.documents.templates', compact('templates', 'clients', 'connections'));
    }
}
