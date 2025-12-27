<?php

namespace App\Http\Livewire\WhiteLabel;

use App\Models\Client;
use App\Models\WhiteLabelConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class WhiteLabelConfigurator extends Component
{
    public ?int $clientId = null;
    public string $customDomain = '';
    public string $logoUrl = '';
    public string $primaryColor = '#3c8dbc';
    public string $secondaryColor = '#6c757d';
    public string $fontFamily = 'Inter, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, sans-serif';
    public string $companyName = '';
    public string $footerText = '';
    public bool $isActive = false;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
    }

    public function loadClient(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        if (!$this->clientId) return;
        $cfg = WhiteLabelConfig::query()->firstOrNew(['client_id' => $this->clientId]);
        $client = Client::query()->find($this->clientId);

        $this->customDomain = (string) ($cfg->custom_domain ?? '');
        $this->logoUrl = (string) ($cfg->logo_url ?? '');
        $this->primaryColor = (string) ($cfg->primary_color ?? '#3c8dbc');
        $this->secondaryColor = (string) ($cfg->secondary_color ?? '#6c757d');
        $this->fontFamily = (string) ($cfg->font_family ?? $this->fontFamily);
        $this->companyName = (string) ($cfg->company_name ?? ($client?->company_name ?? ''));
        $this->footerText = (string) ($cfg->footer_text ?? '');
        $this->isActive = (bool) ($cfg->is_active ?? false);
    }

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make([
            'clientId' => $this->clientId,
            'customDomain' => $this->customDomain,
            'logoUrl' => $this->logoUrl,
            'primaryColor' => $this->primaryColor,
            'secondaryColor' => $this->secondaryColor,
            'companyName' => $this->companyName,
            'isActive' => $this->isActive,
        ], [
            'clientId' => ['required', 'integer', 'exists:clients,id'],
            'customDomain' => ['nullable', 'string', 'max:255'],
            'logoUrl' => ['nullable', 'string', 'max:255'],
            'primaryColor' => ['nullable', 'string', 'max:20'],
            'secondaryColor' => ['nullable', 'string', 'max:20'],
            'companyName' => ['nullable', 'string', 'max:255'],
        ])->validate();

        WhiteLabelConfig::updateOrCreate(
            ['client_id' => (int) $this->clientId],
            [
                'custom_domain' => trim($this->customDomain) ?: null,
                'logo_url' => trim($this->logoUrl) ?: null,
                'primary_color' => trim($this->primaryColor) ?: null,
                'secondary_color' => trim($this->secondaryColor) ?: null,
                'font_family' => trim($this->fontFamily) ?: null,
                'company_name' => trim($this->companyName) ?: null,
                'footer_text' => trim($this->footerText) ?: null,
                'is_active' => (bool) $this->isActive,
            ]
        );

        session()->flash('success', 'White-label settings saved.');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $clients = Client::query()->orderBy('company_name')->limit(250)->get(['id', 'company_name']);
        return view('livewire.white-label.white-label-configurator', [
            'clients' => $clients,
        ])->layout('layouts.admin', ['title' => 'White Label Configurator']);
    }
}

