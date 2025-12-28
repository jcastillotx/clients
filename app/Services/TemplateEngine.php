<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TemplateEngine
{
    /**
     * Available variable categories and their variables.
     */
    public static function getAvailableVariables(): array
    {
        return [
            'client' => [
                'label' => 'Client Information',
                'variables' => [
                    'client_name' => 'Primary contact name',
                    'client_email' => 'Primary contact email',
                    'client_phone' => 'Primary contact phone',
                    'company_name' => 'Company/Organization name',
                    'company_address' => 'Full address',
                    'company_city' => 'City',
                    'company_state' => 'State/Province',
                    'company_zip' => 'ZIP/Postal code',
                    'company_country' => 'Country',
                    'company_website' => 'Website URL',
                    'client_tier' => 'Service tier (basic, premium, etc.)',
                    'client_since' => 'Client since date',
                ],
            ],
            'company' => [
                'label' => 'Your Company',
                'variables' => [
                    'your_company_name' => 'Your company name',
                    'your_company_address' => 'Your company address',
                    'your_company_phone' => 'Your company phone',
                    'your_company_email' => 'Your company email',
                    'your_company_website' => 'Your company website',
                ],
            ],
            'project' => [
                'label' => 'Project Information',
                'variables' => [
                    'project_name' => 'Project name',
                    'project_description' => 'Project description',
                    'project_status' => 'Current status',
                    'project_start_date' => 'Start date',
                    'project_end_date' => 'End date',
                    'project_budget' => 'Budget amount',
                ],
            ],
            'invoice' => [
                'label' => 'Invoice Information',
                'variables' => [
                    'invoice_number' => 'Invoice number',
                    'invoice_date' => 'Invoice date',
                    'invoice_due_date' => 'Due date',
                    'invoice_subtotal' => 'Subtotal amount',
                    'invoice_tax' => 'Tax amount',
                    'invoice_total' => 'Total amount',
                    'invoice_status' => 'Payment status',
                    'invoice_items' => 'Line items (use with #each)',
                ],
            ],
            'contract' => [
                'label' => 'Contract Information',
                'variables' => [
                    'contract_number' => 'Contract number',
                    'contract_title' => 'Contract title',
                    'contract_start_date' => 'Start date',
                    'contract_end_date' => 'End date',
                    'contract_value' => 'Contract value',
                    'contract_status' => 'Current status',
                ],
            ],
            'request' => [
                'label' => 'Service Request',
                'variables' => [
                    'request_title' => 'Request title',
                    'request_description' => 'Request description',
                    'request_type' => 'Request type',
                    'request_priority' => 'Priority level',
                    'request_status' => 'Current status',
                    'request_created_date' => 'Created date',
                ],
            ],
            'dates' => [
                'label' => 'Date & Time',
                'variables' => [
                    'current_date' => 'Current date (formatted)',
                    'current_date_long' => 'Current date (long format)',
                    'current_time' => 'Current time',
                    'current_year' => 'Current year',
                    'current_month' => 'Current month name',
                ],
            ],
            'user' => [
                'label' => 'Current User',
                'variables' => [
                    'user_name' => 'User\'s full name',
                    'user_email' => 'User\'s email',
                    'user_title' => 'User\'s job title',
                ],
            ],
        ];
    }

    /**
     * Get a flat list of all variable names.
     */
    public static function getAllVariableNames(): array
    {
        $names = [];
        foreach (self::getAvailableVariables() as $category => $data) {
            $names = array_merge($names, array_keys($data['variables']));
        }
        return $names;
    }

    /**
     * Render a template with the given context.
     */
    public function render(string $template, array $context = []): string
    {
        // Build variables from context
        $variables = $this->buildVariables($context);

        // Process conditionals first
        $template = $this->processConditionals($template, $variables);

        // Process loops
        $template = $this->processLoops($template, $variables);

        // Process simple variable replacements
        $template = $this->processVariables($template, $variables);

        return $template;
    }

    /**
     * Build the variables array from the context objects.
     */
    protected function buildVariables(array $context): array
    {
        $vars = [];

        // Client variables
        if (isset($context['client']) && $context['client'] instanceof Client) {
            $client = $context['client'];
            $vars['client_name'] = $client->contact_name ?? '';
            $vars['client_email'] = $client->email ?? '';
            $vars['client_phone'] = $client->phone ?? '';
            $vars['company_name'] = $client->company_name ?? '';
            $vars['company_address'] = $client->address ?? '';
            $vars['company_city'] = $client->city ?? '';
            $vars['company_state'] = $client->state ?? '';
            $vars['company_zip'] = $client->zip ?? '';
            $vars['company_country'] = $client->country ?? '';
            $vars['company_website'] = $client->website ?? '';
            $vars['client_tier'] = $client->tier ?? '';
            $vars['client_since'] = $client->created_at?->format('F j, Y') ?? '';
        }

        // Your company variables (from settings)
        $settings = app(SettingsService::class);
        $vars['your_company_name'] = $settings->get('general.company_name', config('app.name'));
        $vars['your_company_address'] = $settings->get('general.address', '');
        $vars['your_company_phone'] = $settings->get('general.phone', '');
        $vars['your_company_email'] = $settings->get('email.from.address', '');
        $vars['your_company_website'] = $settings->get('general.website', '');

        // Project variables
        if (isset($context['project']) && $context['project'] instanceof Project) {
            $project = $context['project'];
            $vars['project_name'] = $project->name ?? '';
            $vars['project_description'] = $project->description ?? '';
            $vars['project_status'] = $project->status ?? '';
            $vars['project_start_date'] = $project->start_date?->format('F j, Y') ?? '';
            $vars['project_end_date'] = $project->end_date?->format('F j, Y') ?? '';
            $vars['project_budget'] = $project->budget ? number_format($project->budget, 2) : '';
        }

        // Invoice variables
        if (isset($context['invoice']) && $context['invoice'] instanceof Invoice) {
            $invoice = $context['invoice'];
            $vars['invoice_number'] = $invoice->invoice_number ?? '';
            $vars['invoice_date'] = $invoice->issue_date?->format('F j, Y') ?? '';
            $vars['invoice_due_date'] = $invoice->due_date?->format('F j, Y') ?? '';
            $vars['invoice_subtotal'] = number_format($invoice->subtotal ?? 0, 2);
            $vars['invoice_tax'] = number_format($invoice->tax_amount ?? 0, 2);
            $vars['invoice_total'] = number_format($invoice->total ?? 0, 2);
            $vars['invoice_status'] = $invoice->status ?? '';
            
            // Invoice items for loops
            $vars['invoice_items'] = $invoice->items?->map(function ($item) {
                return [
                    'description' => $item->description ?? '',
                    'quantity' => $item->quantity ?? 1,
                    'rate' => number_format($item->unit_price ?? 0, 2),
                    'amount' => number_format(($item->quantity ?? 1) * ($item->unit_price ?? 0), 2),
                ];
            })->toArray() ?? [];
        }

        // Contract variables
        if (isset($context['contract']) && $context['contract'] instanceof Contract) {
            $contract = $context['contract'];
            $vars['contract_number'] = $contract->contract_number ?? '';
            $vars['contract_title'] = $contract->title ?? '';
            $vars['contract_start_date'] = $contract->start_date?->format('F j, Y') ?? '';
            $vars['contract_end_date'] = $contract->end_date?->format('F j, Y') ?? '';
            $vars['contract_value'] = $contract->value ? number_format($contract->value, 2) : '';
            $vars['contract_status'] = $contract->status ?? '';
        }

        // Service request variables
        if (isset($context['request']) && $context['request'] instanceof ServiceRequest) {
            $request = $context['request'];
            $vars['request_title'] = $request->title ?? '';
            $vars['request_description'] = $request->description ?? '';
            $vars['request_type'] = $request->type ?? '';
            $vars['request_priority'] = $request->priority ?? '';
            $vars['request_status'] = $request->status ?? '';
            $vars['request_created_date'] = $request->created_at?->format('F j, Y') ?? '';
        }

        // Date variables
        $now = Carbon::now();
        $vars['current_date'] = $now->format('m/d/Y');
        $vars['current_date_long'] = $now->format('F j, Y');
        $vars['current_time'] = $now->format('g:i A');
        $vars['current_year'] = $now->format('Y');
        $vars['current_month'] = $now->format('F');

        // User variables
        if (isset($context['user']) && $context['user'] instanceof User) {
            $user = $context['user'];
            $vars['user_name'] = $user->name ?? '';
            $vars['user_email'] = $user->email ?? '';
            $vars['user_title'] = $user->title ?? '';
        } elseif (auth()->check()) {
            $user = auth()->user();
            $vars['user_name'] = $user->name ?? '';
            $vars['user_email'] = $user->email ?? '';
            $vars['user_title'] = $user->title ?? '';
        }

        // Merge any custom variables
        if (isset($context['custom']) && is_array($context['custom'])) {
            $vars = array_merge($vars, $context['custom']);
        }

        return $vars;
    }

    /**
     * Process simple variable replacements: {{variable_name}}
     */
    protected function processVariables(string $template, array $variables): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function ($matches) use ($variables) {
                $key = $matches[1];
                if (isset($variables[$key])) {
                    $value = $variables[$key];
                    // Don't escape if it's an array (for loops) or already HTML
                    if (is_array($value)) {
                        return $matches[0]; // Keep as-is, loops should have processed it
                    }
                    return e((string) $value);
                }
                return $matches[0]; // Keep original if not found
            },
            $template
        ) ?? $template;
    }

    /**
     * Process conditional blocks: {{#if variable}}...{{/if}} and {{#if variable}}...{{else}}...{{/if}}
     */
    protected function processConditionals(string $template, array $variables): string
    {
        // Process if/else/endif blocks
        $pattern = '/\{\{#if\s+([a-zA-Z0-9_]+)\s*\}\}(.*?)(?:\{\{else\}\}(.*?))?\{\{\/if\}\}/s';
        
        return preg_replace_callback(
            $pattern,
            function ($matches) use ($variables) {
                $condition = $matches[1];
                $trueBlock = $matches[2];
                $falseBlock = $matches[3] ?? '';

                $value = $variables[$condition] ?? null;
                $isTruthy = !empty($value) && $value !== '0' && $value !== 'false';

                return $isTruthy ? $trueBlock : $falseBlock;
            },
            $template
        ) ?? $template;
    }

    /**
     * Process loop blocks: {{#each items}}...{{/each}}
     */
    protected function processLoops(string $template, array $variables): string
    {
        $pattern = '/\{\{#each\s+([a-zA-Z0-9_]+)\s*\}\}(.*?)\{\{\/each\}\}/s';

        return preg_replace_callback(
            $pattern,
            function ($matches) use ($variables) {
                $arrayName = $matches[1];
                $itemTemplate = $matches[2];

                $items = $variables[$arrayName] ?? [];
                if (!is_array($items) || empty($items)) {
                    return '';
                }

                $output = '';
                $index = 0;
                foreach ($items as $item) {
                    $itemOutput = $itemTemplate;
                    
                    // Replace {{@index}} with the current index
                    $itemOutput = str_replace('{{@index}}', (string) $index, $itemOutput);
                    $itemOutput = str_replace('{{@number}}', (string) ($index + 1), $itemOutput);
                    
                    // Replace {{@first}} and {{@last}} conditions
                    $isFirst = $index === 0;
                    $isLast = $index === count($items) - 1;
                    
                    // Replace item properties: {{this.property}}
                    if (is_array($item)) {
                        foreach ($item as $key => $value) {
                            $itemOutput = preg_replace(
                                '/\{\{\s*this\.' . preg_quote($key, '/') . '\s*\}\}/',
                                e((string) $value),
                                $itemOutput
                            ) ?? $itemOutput;
                        }
                    }

                    $output .= $itemOutput;
                    $index++;
                }

                return $output;
            },
            $template
        ) ?? $template;
    }

    /**
     * Preview a template with sample data.
     */
    public function preview(string $template): string
    {
        $sampleContext = [
            'custom' => [
                'client_name' => 'John Smith',
                'client_email' => 'john.smith@example.com',
                'client_phone' => '(555) 123-4567',
                'company_name' => 'Acme Corporation',
                'company_address' => '123 Business Ave',
                'company_city' => 'New York',
                'company_state' => 'NY',
                'company_zip' => '10001',
                'company_country' => 'United States',
                'company_website' => 'https://acme.example.com',
                'client_tier' => 'Premium',
                'client_since' => 'January 15, 2023',
                
                'project_name' => 'Website Redesign',
                'project_description' => 'Complete overhaul of the corporate website',
                'project_status' => 'In Progress',
                'project_start_date' => 'March 1, 2024',
                'project_end_date' => 'June 30, 2024',
                'project_budget' => '25,000.00',
                
                'invoice_number' => 'INV-2024-001',
                'invoice_date' => 'March 15, 2024',
                'invoice_due_date' => 'April 15, 2024',
                'invoice_subtotal' => '5,000.00',
                'invoice_tax' => '400.00',
                'invoice_total' => '5,400.00',
                'invoice_status' => 'Pending',
                'invoice_items' => [
                    ['description' => 'Web Design Services', 'quantity' => 40, 'rate' => '100.00', 'amount' => '4,000.00'],
                    ['description' => 'Content Writing', 'quantity' => 10, 'rate' => '100.00', 'amount' => '1,000.00'],
                ],
                
                'contract_number' => 'CON-2024-001',
                'contract_title' => 'Website Development Agreement',
                'contract_start_date' => 'March 1, 2024',
                'contract_end_date' => 'December 31, 2024',
                'contract_value' => '50,000.00',
                'contract_status' => 'Active',
                
                'request_title' => 'Homepage Banner Update',
                'request_description' => 'Update the hero banner with new promotional content',
                'request_type' => 'Design',
                'request_priority' => 'High',
                'request_status' => 'In Progress',
                'request_created_date' => 'March 10, 2024',
            ],
        ];

        return $this->render($template, $sampleContext);
    }

    /**
     * Validate a template and return any errors.
     */
    public function validate(string $template): array
    {
        $errors = [];

        // Check for unclosed conditionals
        $ifCount = preg_match_all('/\{\{#if\s/', $template);
        $endIfCount = preg_match_all('/\{\{\/if\}\}/', $template);
        if ($ifCount !== $endIfCount) {
            $errors[] = 'Mismatched {{#if}} and {{/if}} blocks. Found ' . $ifCount . ' opening and ' . $endIfCount . ' closing.';
        }

        // Check for unclosed loops
        $eachCount = preg_match_all('/\{\{#each\s/', $template);
        $endEachCount = preg_match_all('/\{\{\/each\}\}/', $template);
        if ($eachCount !== $endEachCount) {
            $errors[] = 'Mismatched {{#each}} and {{/each}} blocks. Found ' . $eachCount . ' opening and ' . $endEachCount . ' closing.';
        }

        // Check for unknown variables (warning, not error)
        $knownVars = self::getAllVariableNames();
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $template, $matches);
        $usedVars = array_unique($matches[1] ?? []);
        
        foreach ($usedVars as $var) {
            if (!in_array($var, $knownVars) && !str_starts_with($var, '@')) {
                // It might be a custom variable, just warn
            }
        }

        return $errors;
    }

    /**
     * Extract all variables used in a template.
     */
    public function extractVariables(string $template): array
    {
        $variables = [];

        // Simple variables
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $template, $matches);
        $variables = array_merge($variables, $matches[1] ?? []);

        // Conditional variables
        preg_match_all('/\{\{#if\s+([a-zA-Z0-9_]+)\s*\}\}/', $template, $matches);
        $variables = array_merge($variables, $matches[1] ?? []);

        // Loop variables
        preg_match_all('/\{\{#each\s+([a-zA-Z0-9_]+)\s*\}\}/', $template, $matches);
        $variables = array_merge($variables, $matches[1] ?? []);

        return array_unique($variables);
    }
}
