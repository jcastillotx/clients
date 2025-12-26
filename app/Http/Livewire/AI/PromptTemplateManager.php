<?php

namespace App\Http\Livewire\AI;

use App\Models\PromptTemplate;
use App\Models\PromptTemplateVersion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PromptTemplateManager extends Component
{
    public ?int $templateId = null;

    public string $key = '';
    public string $name = '';
    public string $description = '';
    public string $status = 'active';

    public string $system_prompt = '';
    public string $variables_json = '{}';
    public string $notes = '';
    public string $version_status = 'draft';

    public function selectTemplate(int $id): void
    {
        $this->authorizeAdmin();
        $tpl = PromptTemplate::query()->findOrFail($id);
        $this->templateId = $tpl->id;
        $this->key = $tpl->key;
        $this->name = $tpl->name;
        $this->description = (string) ($tpl->description ?? '');
        $this->status = $tpl->status;

        $this->system_prompt = '';
        $this->variables_json = '{}';
        $this->notes = '';
        $this->version_status = 'draft';
    }

    public function createTemplate(): void
    {
        $this->authorizeAdmin();
        $data = $this->validate([
            'key' => 'required|string|max:120',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,inactive',
        ]);

        $tpl = PromptTemplate::create($data);
        $this->selectTemplate($tpl->id);
        session()->flash('success', 'Template created.');
    }

    public function saveTemplate(): void
    {
        $this->authorizeAdmin();
        if (!$this->templateId) return;

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,inactive',
        ]);

        PromptTemplate::query()->whereKey($this->templateId)->update($data);
        session()->flash('success', 'Template updated.');
    }

    public function addVersion(): void
    {
        $this->authorizeAdmin();
        if (!$this->templateId) return;

        $vars = json_decode($this->variables_json, true);
        if (!is_array($vars)) $vars = [];

        $latest = PromptTemplateVersion::query()
            ->where('prompt_template_id', $this->templateId)
            ->orderByDesc('version')
            ->first();
        $next = $latest ? ((int) $latest->version + 1) : 1;

        PromptTemplateVersion::create([
            'prompt_template_id' => $this->templateId,
            'version' => $next,
            'status' => $this->version_status,
            'system_prompt' => $this->system_prompt,
            'variables' => $vars ?: null,
            'notes' => $this->notes ?: null,
        ]);

        session()->flash('success', "Version v{$next} created.");
        $this->system_prompt = '';
        $this->notes = '';
        $this->variables_json = '{}';
    }

    public function activateVersion(int $versionId): void
    {
        $this->authorizeAdmin();
        $v = PromptTemplateVersion::query()->findOrFail($versionId);
        PromptTemplateVersion::query()
            ->where('prompt_template_id', $v->prompt_template_id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);
        $v->update(['status' => 'active']);
        session()->flash('success', 'Version activated.');
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (!$u || !$u->can('access admin panel')) abort(403);
    }

    public function render()
    {
        $this->authorizeAdmin();

        $templates = PromptTemplate::query()->orderBy('key')->get();
        $versions = $this->templateId
            ? PromptTemplateVersion::query()->where('prompt_template_id', $this->templateId)->orderByDesc('version')->get()
            : collect();

        return view('livewire.ai.prompt-templates', [
            'templates' => $templates,
            'versions' => $versions,
        ])->layout('layouts.admin', ['title' => 'Prompt Templates']);
    }
}

