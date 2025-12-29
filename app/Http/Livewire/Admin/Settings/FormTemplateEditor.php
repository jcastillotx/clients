<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\FormTemplate;
use App\Services\FormTemplateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class FormTemplateEditor extends Component
{
    public ?int $templateId = null;

    public string $slug = '';

    public string $name = '';

    public string $description = '';

    public array $fields = [];

    public array $baselineFields = [];

    // New field form
    public bool $showAddField = false;

    public string $newFieldKey = '';

    public string $newFieldLabel = '';

    public string $newFieldType = 'text';

    public bool $newFieldRequired = false;

    public string $newFieldOptions = '';

    // Edit field form
    public ?int $editingFieldIndex = null;

    public string $editFieldKey = '';

    public string $editFieldLabel = '';

    public string $editFieldType = 'text';

    public bool $editFieldRequired = false;

    public string $editFieldOptions = '';

    protected $listeners = ['reorderFields'];

    public function mount(FormTemplateService $service, string $slug): void
    {
        abort_unless(Auth::user()?->can('manage settings'), 403);

        $template = $service->getTemplate($slug);

        if (! $template) {
            session()->flash('error', 'Form template not found.');

            return;
        }

        $this->templateId = $template->id;
        $this->slug = $template->slug;
        $this->name = $template->name;
        $this->description = $template->description ?? '';
        $this->fields = $template->fields ?? [];
        $this->baselineFields = $template->baseline_fields ?? [];
    }

    public function isBaseline(string $key): bool
    {
        return in_array($key, $this->baselineFields, true);
    }

    public function toggleAddField(): void
    {
        $this->showAddField = ! $this->showAddField;
        $this->resetNewFieldForm();
    }

    public function resetNewFieldForm(): void
    {
        $this->newFieldKey = '';
        $this->newFieldLabel = '';
        $this->newFieldType = 'text';
        $this->newFieldRequired = false;
        $this->newFieldOptions = '';
    }

    public function addField(): void
    {
        // Auto-generate key from label if not provided
        if (empty($this->newFieldKey) && ! empty($this->newFieldLabel)) {
            $this->newFieldKey = Str::snake(Str::ascii($this->newFieldLabel));
        }

        Validator::make([
            'key' => $this->newFieldKey,
            'label' => $this->newFieldLabel,
            'type' => $this->newFieldType,
        ], [
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(FormTemplateService::$fieldTypes))],
        ], [
            'key.regex' => 'Field key must be lowercase letters, numbers, and underscores only, starting with a letter.',
        ])->validate();

        // Check for duplicate key
        foreach ($this->fields as $field) {
            if (($field['key'] ?? '') === $this->newFieldKey) {
                session()->flash('error', 'A field with this key already exists.');

                return;
            }
        }

        $options = [];
        if (in_array($this->newFieldType, ['select', 'multiselect'])) {
            $options = array_map('trim', explode("\n", $this->newFieldOptions));
            $options = array_filter($options);
        }

        $this->fields[] = [
            'key' => $this->newFieldKey,
            'label' => $this->newFieldLabel,
            'type' => $this->newFieldType,
            'required' => $this->newFieldRequired,
            'options' => array_values($options),
        ];

        $this->showAddField = false;
        $this->resetNewFieldForm();
        $this->saveFields();
    }

    public function editField(int $index): void
    {
        if (! isset($this->fields[$index])) {
            return;
        }

        $field = $this->fields[$index];
        $this->editingFieldIndex = $index;
        $this->editFieldKey = $field['key'] ?? '';
        $this->editFieldLabel = $field['label'] ?? '';
        $this->editFieldType = $field['type'] ?? 'text';
        $this->editFieldRequired = (bool) ($field['required'] ?? false);
        $this->editFieldOptions = implode("\n", $field['options'] ?? []);
    }

    public function cancelEdit(): void
    {
        $this->editingFieldIndex = null;
    }

    public function saveFieldEdit(): void
    {
        if ($this->editingFieldIndex === null || ! isset($this->fields[$this->editingFieldIndex])) {
            return;
        }

        $isBaseline = $this->isBaseline($this->fields[$this->editingFieldIndex]['key'] ?? '');

        Validator::make([
            'label' => $this->editFieldLabel,
            'type' => $this->editFieldType,
        ], [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(FormTemplateService::$fieldTypes))],
        ])->validate();

        $options = [];
        if (in_array($this->editFieldType, ['select', 'multiselect'])) {
            $options = array_map('trim', explode("\n", $this->editFieldOptions));
            $options = array_filter($options);
        }

        // For baseline fields, we can't change the key
        $this->fields[$this->editingFieldIndex] = [
            'key' => $this->fields[$this->editingFieldIndex]['key'], // Keep original key
            'label' => $this->editFieldLabel,
            'type' => $isBaseline ? $this->fields[$this->editingFieldIndex]['type'] : $this->editFieldType, // Baseline fields keep their type
            'required' => $isBaseline ? $this->fields[$this->editingFieldIndex]['required'] : $this->editFieldRequired, // Baseline fields stay required
            'options' => array_values($options),
        ];

        $this->editingFieldIndex = null;
        $this->saveFields();
    }

    public function deleteField(int $index): void
    {
        if (! isset($this->fields[$index])) {
            return;
        }

        $fieldKey = $this->fields[$index]['key'] ?? '';

        if ($this->isBaseline($fieldKey)) {
            session()->flash('error', 'Cannot delete baseline field: '.$fieldKey);

            return;
        }

        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
        $this->saveFields();
    }

    public function reorderFields(array $order): void
    {
        $newFields = [];
        foreach ($order as $index) {
            if (isset($this->fields[$index])) {
                $newFields[] = $this->fields[$index];
            }
        }
        $this->fields = $newFields;
        $this->saveFields();
    }

    public function moveFieldUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->fields[$index])) {
            return;
        }

        $temp = $this->fields[$index - 1];
        $this->fields[$index - 1] = $this->fields[$index];
        $this->fields[$index] = $temp;
        $this->saveFields();
    }

    public function moveFieldDown(int $index): void
    {
        if ($index >= count($this->fields) - 1 || ! isset($this->fields[$index])) {
            return;
        }

        $temp = $this->fields[$index + 1];
        $this->fields[$index + 1] = $this->fields[$index];
        $this->fields[$index] = $temp;
        $this->saveFields();
    }

    public function saveFields(): void
    {
        if (! $this->templateId) {
            return;
        }

        FormTemplate::where('id', $this->templateId)->update([
            'fields' => $this->fields,
        ]);

        session()->flash('success', 'Form fields saved successfully.');
    }

    public function resetToDefaults(FormTemplateService $service): void
    {
        try {
            $template = $service->resetToDefaults($this->slug);
            $this->fields = $template->fields ?? [];
            $this->baselineFields = $template->baseline_fields ?? [];
            session()->flash('success', 'Form reset to default fields.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.form-template-editor', [
            'fieldTypes' => FormTemplateService::$fieldTypes,
        ])->layout('layouts.admin');
    }
}
