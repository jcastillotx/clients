<?php

namespace App\Services;

use App\Models\FormTemplate;

class FormTemplateService
{
    /**
     * Default form template definitions.
     * Each form has baseline fields that cannot be deleted.
     */
    public static array $defaults = [
        'onboarding' => [
            'name' => 'Brand Discovery / Onboarding',
            'description' => 'Questionnaire for new client onboarding and brand discovery.',
            'baseline_fields' => ['target_audience', 'brand_personality', 'competitors', 'goals'],
            'fields' => [
                ['key' => 'target_audience', 'type' => 'textarea', 'label' => 'Who is your target audience?', 'required' => true, 'options' => []],
                ['key' => 'audience_segments', 'type' => 'textarea', 'label' => 'Describe key audience segments (if multiple).', 'required' => false, 'options' => []],
                ['key' => 'brand_personality', 'type' => 'multiselect', 'label' => 'Brand personality (pick up to 5)', 'required' => true, 'options' => ['Professional', 'Friendly', 'Bold', 'Playful', 'Luxury', 'Minimal', 'Innovative', 'Trustworthy', 'Community-first', 'Technical']],
                ['key' => 'brand_voice_examples', 'type' => 'textarea', 'label' => 'Share examples of brands whose voice you like (and why).', 'required' => false, 'options' => []],
                ['key' => 'competitors', 'type' => 'textarea', 'label' => 'Who are your top competitors? Include websites if possible.', 'required' => true, 'options' => []],
                ['key' => 'differentiators', 'type' => 'textarea', 'label' => 'What differentiates you from competitors?', 'required' => true, 'options' => []],
                ['key' => 'goals', 'type' => 'textarea', 'label' => 'What are your business goals for the next 3–6 months?', 'required' => true, 'options' => []],
                ['key' => 'kpis', 'type' => 'textarea', 'label' => 'What KPIs matter most? (e.g., leads, revenue, CAC, ROAS)', 'required' => true, 'options' => []],
                ['key' => 'budget_range', 'type' => 'select', 'label' => 'Monthly marketing budget range', 'required' => false, 'options' => ['<$1k', '$1k–$3k', '$3k–$7k', '$7k–$15k', '$15k+']],
                ['key' => 'timeline', 'type' => 'textarea', 'label' => 'Any deadlines or timeline constraints?', 'required' => false, 'options' => []],
                ['key' => 'previous_efforts', 'type' => 'textarea', 'label' => 'What marketing have you tried before? What worked / didn't?', 'required' => false, 'options' => []],
                ['key' => 'pain_points', 'type' => 'textarea', 'label' => 'What are your biggest pain points and challenges right now?', 'required' => true, 'options' => []],
            ],
        ],
        'service_request' => [
            'name' => 'Service Request',
            'description' => 'Form fields for submitting service requests.',
            'baseline_fields' => ['title', 'description', 'type', 'priority'],
            'fields' => [
                ['key' => 'title', 'type' => 'text', 'label' => 'Request Title', 'required' => true, 'options' => []],
                ['key' => 'description', 'type' => 'textarea', 'label' => 'Description', 'required' => true, 'options' => []],
                ['key' => 'type', 'type' => 'select', 'label' => 'Request Type', 'required' => true, 'options' => ['support', 'feature', 'bug', 'consultation', 'design', 'development', 'marketing', 'other']],
                ['key' => 'priority', 'type' => 'select', 'label' => 'Priority', 'required' => true, 'options' => ['low', 'medium', 'high', 'urgent']],
                ['key' => 'deadline', 'type' => 'date', 'label' => 'Desired Deadline', 'required' => false, 'options' => []],
                ['key' => 'budget', 'type' => 'text', 'label' => 'Budget (if applicable)', 'required' => false, 'options' => []],
                ['key' => 'references', 'type' => 'textarea', 'label' => 'Reference Links or Examples', 'required' => false, 'options' => []],
            ],
        ],
        'meeting' => [
            'name' => 'Meeting Request',
            'description' => 'Form fields for scheduling meetings.',
            'baseline_fields' => ['title', 'meeting_type', 'scheduled_at', 'duration_minutes'],
            'fields' => [
                ['key' => 'title', 'type' => 'text', 'label' => 'Meeting Title', 'required' => true, 'options' => []],
                ['key' => 'meeting_type', 'type' => 'select', 'label' => 'Meeting Type', 'required' => true, 'options' => ['kickoff', 'strategy', 'review', 'other']],
                ['key' => 'scheduled_at', 'type' => 'datetime', 'label' => 'Preferred Date & Time', 'required' => true, 'options' => []],
                ['key' => 'duration_minutes', 'type' => 'select', 'label' => 'Duration', 'required' => true, 'options' => ['15', '30', '45', '60', '90', '120']],
                ['key' => 'agenda', 'type' => 'textarea', 'label' => 'Meeting Agenda', 'required' => false, 'options' => []],
                ['key' => 'attendees', 'type' => 'textarea', 'label' => 'Additional Attendees (emails)', 'required' => false, 'options' => []],
            ],
        ],
        'feedback' => [
            'name' => 'Feedback / Survey',
            'description' => 'Form fields for collecting client feedback.',
            'baseline_fields' => ['overall_satisfaction', 'comments'],
            'fields' => [
                ['key' => 'overall_satisfaction', 'type' => 'select', 'label' => 'Overall Satisfaction', 'required' => true, 'options' => ['1 - Very Dissatisfied', '2 - Dissatisfied', '3 - Neutral', '4 - Satisfied', '5 - Very Satisfied']],
                ['key' => 'communication_rating', 'type' => 'select', 'label' => 'Communication Quality', 'required' => false, 'options' => ['1 - Poor', '2 - Fair', '3 - Good', '4 - Very Good', '5 - Excellent']],
                ['key' => 'timeliness_rating', 'type' => 'select', 'label' => 'Timeliness of Delivery', 'required' => false, 'options' => ['1 - Poor', '2 - Fair', '3 - Good', '4 - Very Good', '5 - Excellent']],
                ['key' => 'quality_rating', 'type' => 'select', 'label' => 'Quality of Work', 'required' => false, 'options' => ['1 - Poor', '2 - Fair', '3 - Good', '4 - Very Good', '5 - Excellent']],
                ['key' => 'would_recommend', 'type' => 'select', 'label' => 'Would you recommend us?', 'required' => false, 'options' => ['Yes', 'Maybe', 'No']],
                ['key' => 'comments', 'type' => 'textarea', 'label' => 'Additional Comments', 'required' => false, 'options' => []],
            ],
        ],
    ];

    /**
     * Available field types for the form builder.
     */
    public static array $fieldTypes = [
        'text' => 'Single Line Text',
        'textarea' => 'Multi-line Text',
        'select' => 'Dropdown Select',
        'multiselect' => 'Multi-select (Checkboxes)',
        'date' => 'Date Picker',
        'datetime' => 'Date & Time Picker',
        'number' => 'Number',
        'email' => 'Email',
        'url' => 'URL',
        'checkbox' => 'Single Checkbox',
    ];

    /**
     * Ensure all default form templates exist in the database.
     */
    public function seedDefaults(): void
    {
        foreach (self::$defaults as $slug => $definition) {
            FormTemplate::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'fields' => $definition['fields'],
                    'baseline_fields' => $definition['baseline_fields'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Get a form template by slug, seeding defaults if needed.
     */
    public function getTemplate(string $slug): ?FormTemplate
    {
        $template = FormTemplate::bySlug($slug)->first();

        // If not found and we have a default, create it
        if (! $template && isset(self::$defaults[$slug])) {
            $definition = self::$defaults[$slug];
            $template = FormTemplate::create([
                'slug' => $slug,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'fields' => $definition['fields'],
                'baseline_fields' => $definition['baseline_fields'],
                'is_active' => true,
            ]);
        }

        return $template;
    }

    /**
     * Get all form templates, seeding any missing defaults.
     */
    public function getAllTemplates(): array
    {
        // Ensure all defaults exist
        $this->seedDefaults();

        return FormTemplate::orderBy('name')->get()->all();
    }

    /**
     * Get fields for a form template.
     */
    public function getFields(string $slug): array
    {
        $template = $this->getTemplate($slug);

        return $template ? ($template->fields ?? []) : [];
    }

    /**
     * Update fields for a form template.
     */
    public function updateFields(string $slug, array $fields): FormTemplate
    {
        $template = $this->getTemplate($slug);

        if (! $template) {
            throw new \InvalidArgumentException("Form template '{$slug}' not found.");
        }

        $template->update(['fields' => $fields]);

        return $template->fresh();
    }

    /**
     * Reset a form template to its default fields.
     */
    public function resetToDefaults(string $slug): FormTemplate
    {
        $template = $this->getTemplate($slug);

        if (! $template) {
            throw new \InvalidArgumentException("Form template '{$slug}' not found.");
        }

        if (! isset(self::$defaults[$slug])) {
            throw new \InvalidArgumentException("No default definition for form template '{$slug}'.");
        }

        $default = self::$defaults[$slug];
        $template->update([
            'fields' => $default['fields'],
            'baseline_fields' => $default['baseline_fields'],
        ]);

        return $template->fresh();
    }

    /**
     * Add a new field to a form template.
     */
    public function addField(string $slug, array $field): FormTemplate
    {
        $template = $this->getTemplate($slug);

        if (! $template) {
            throw new \InvalidArgumentException("Form template '{$slug}' not found.");
        }

        $fields = $template->fields ?? [];
        $fields[] = $field;
        $template->update(['fields' => $fields]);

        return $template->fresh();
    }

    /**
     * Remove a field from a form template (if not baseline).
     */
    public function removeField(string $slug, string $fieldKey): FormTemplate
    {
        $template = $this->getTemplate($slug);

        if (! $template) {
            throw new \InvalidArgumentException("Form template '{$slug}' not found.");
        }

        if ($template->isBaselineField($fieldKey)) {
            throw new \InvalidArgumentException("Cannot delete baseline field '{$fieldKey}'.");
        }

        $fields = array_filter(
            $template->fields ?? [],
            fn ($f) => ($f['key'] ?? null) !== $fieldKey
        );

        $template->update(['fields' => array_values($fields)]);

        return $template->fresh();
    }
}
