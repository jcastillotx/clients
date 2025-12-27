<?php

namespace App\Http\Livewire\Documents;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\StorageConnection;
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

    // generate
    public ?int $generate_client_id = null;

    public ?int $generate_template_id = null;

    public string $generate_destination = 'local'; // local|connection:{id}

    public string $generate_title = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('manage documents') || Auth::user()?->can('access admin panel'), 403);
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

        DocumentTemplate::create([
            'name' => $this->name,
            'category' => $this->category,
            'body' => $this->body,
            'variables' => $vars,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['name', 'category', 'body', 'variables_csv']);
        session()->flash('success', 'Template saved.');
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
            $conn = StorageConnection::query()->where('client_id', $client->id)->where('status', 'active')->findOrFail($id);
            abort_unless($conn->disk, 422);
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
