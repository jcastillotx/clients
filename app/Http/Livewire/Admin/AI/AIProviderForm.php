<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\AiProvider;
use App\Services\AI\AskSageService;
use App\Services\AI\ClaudeService;
use App\Services\AI\OpenAIService;
use App\Services\AI\OpenRouterService;
use App\Services\AI\PerplexityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AIProviderForm extends Component
{
    public ?int $providerId = null;

    public string $name = 'openai';

    public string $api_key = '';

    public string $api_endpoint = '';

    public string $model_name = '';

    public string $status = 'inactive';

    public ?string $cost_per_1k_input_tokens = null;

    public ?string $cost_per_1k_output_tokens = null;

    public ?int $rate_limit_per_minute = null;

    public bool $is_default = false;

    public int $priority_order = 100;

    public bool $testOk = false;

    public ?string $testMessage = null;

    public function mount(?int $provider = null): void
    {
        $this->authorizeSuperAdmin();

        $this->providerId = $provider;
        if ($provider) {
            $row = AiProvider::query()->findOrFail($provider);
            $this->name = (string) $row->name;
            $this->api_endpoint = (string) ($row->api_endpoint ?? '');
            $this->model_name = (string) ($row->model_name ?? '');
            $this->status = (string) ($row->status ?? 'inactive');
            $this->cost_per_1k_input_tokens = $row->cost_per_1k_input_tokens !== null ? (string) $row->cost_per_1k_input_tokens : null;
            $this->cost_per_1k_output_tokens = $row->cost_per_1k_output_tokens !== null ? (string) $row->cost_per_1k_output_tokens : null;
            $this->rate_limit_per_minute = $row->rate_limit_per_minute;
            $this->is_default = (bool) $row->is_default;
            $this->priority_order = (int) ($row->priority_order ?? 100);
        }
    }

    public function updatedName(): void
    {
        // Pre-fill model defaults when switching provider
        $defaults = (array) config("ai-providers.providers.{$this->name}", []);
        $this->api_endpoint = (string) ($defaults['api_base'] ?? $this->api_endpoint);
        $this->model_name = (string) ($defaults['default_model'] ?? $this->model_name);
    }

    public function getProviderOptionsProperty(): array
    {
        return [
            'openai' => 'OpenAI',
            'claude' => 'Claude (Anthropic)',
            'openrouter' => 'OpenRouter',
            'perplexity' => 'Perplexity',
            'asksage' => 'AskSage',
        ];
    }

    public function getModelOptionsProperty(): array
    {
        $pricing = (array) config("ai-providers.pricing.{$this->name}", []);
        $models = array_keys($pricing);
        sort($models);

        return $models;
    }

    public function testConnection(): void
    {
        $this->authorizeSuperAdmin();

        $this->testOk = false;
        $this->testMessage = null;

        $service = $this->serviceForName($this->name);
        $service->configure([
            'api_key' => $this->api_key ?: ($this->providerId ? AiProvider::query()->find($this->providerId)?->api_key : null),
            'api_base' => $this->api_endpoint ?: null,
            'default_model' => $this->model_name ?: null,
            'cost_per_1k_input_tokens' => $this->cost_per_1k_input_tokens !== null ? (float) $this->cost_per_1k_input_tokens : null,
            'cost_per_1k_output_tokens' => $this->cost_per_1k_output_tokens !== null ? (float) $this->cost_per_1k_output_tokens : null,
            'rate_limit_per_minute' => $this->rate_limit_per_minute,
        ]);

        try {
            $ok = $service->validateApiKey();
            $this->testOk = (bool) $ok;
            $this->testMessage = $ok ? 'Connection OK.' : 'Connection failed.';
        } catch (\Throwable $e) {
            $this->testOk = false;
            $this->testMessage = 'Connection error: '.$e->getMessage();
        }
    }

    public function save(): void
    {
        $this->authorizeSuperAdmin();

        $data = $this->validate([
            'name' => 'required|in:openai,claude,openrouter,perplexity,asksage',
            'api_key' => 'nullable|string',
            'api_endpoint' => 'nullable|string',
            'model_name' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'cost_per_1k_input_tokens' => 'nullable|numeric|min:0',
            'cost_per_1k_output_tokens' => 'nullable|numeric|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'is_default' => 'boolean',
            'priority_order' => 'required|integer|min:0',
        ]);

        /** @var AiProvider $row */
        $row = $this->providerId
            ? AiProvider::query()->findOrFail($this->providerId)
            : new AiProvider;

        // Only overwrite key if a new value is provided.
        if (trim((string) $data['api_key']) === '' && $row->exists) {
            unset($data['api_key']);
        }

        if (($data['is_default'] ?? false) === true) {
            AiProvider::query()->where('name', $data['name'])->update(['is_default' => false]);
            $data['status'] = 'active';
        }

        $row->fill([
            'name' => $data['name'],
            'api_key' => $data['api_key'] ?? $row->api_key,
            'api_endpoint' => $data['api_endpoint'] ?? null,
            'model_name' => $data['model_name'] ?? null,
            'status' => $data['status'],
            'cost_per_1k_input_tokens' => $data['cost_per_1k_input_tokens'] ?? null,
            'cost_per_1k_output_tokens' => $data['cost_per_1k_output_tokens'] ?? null,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'priority_order' => (int) $data['priority_order'],
        ]);
        $row->save();

        session()->flash('success', 'Provider configuration saved.');
        redirect()->route('admin.ai.providers');
    }

    protected function serviceForName(string $name)
    {
        return match ($name) {
            'openai' => app(OpenAIService::class),
            'claude' => app(ClaudeService::class),
            'openrouter' => app(OpenRouterService::class),
            'perplexity' => app(PerplexityService::class),
            'asksage' => app(AskSageService::class),
            default => app(OpenAIService::class),
        };
    }

    protected function authorizeSuperAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->hasRole('super_admin')) {
            abort(403, 'Only super admins can edit AI provider settings.');
        }
    }

    public function render()
    {
        return view('livewire.admin.ai.provider-form', [
            'providerOptions' => $this->providerOptions,
            'modelOptions' => $this->modelOptions,
        ])->layout('layouts.admin', ['title' => $this->providerId ? 'Edit AI Provider' : 'Add AI Provider']);
    }
}
