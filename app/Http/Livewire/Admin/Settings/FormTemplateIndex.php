<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Services\FormTemplateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FormTemplateIndex extends Component
{
    public array $templates = [];

    public function mount(FormTemplateService $service): void
    {
        abort_unless(Auth::user()?->can('manage settings'), 403);

        $this->templates = array_map(
            fn ($t) => [
                'id' => $t->id,
                'slug' => $t->slug,
                'name' => $t->name,
                'description' => $t->description,
                'field_count' => count($t->fields ?? []),
                'is_active' => $t->is_active,
            ],
            $service->getAllTemplates()
        );
    }

    public function render()
    {
        return view('livewire.admin.settings.form-template-index')
            ->layout('layouts.admin');
    }
}
