<?php

namespace App\Http\Livewire\Admin\Clients;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ClientEdit extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public Client $client;

    public ?User $primaryUser = null;

    public string $company_name = '';

    public string $contact_name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $zip_code = null;

    public string $country = 'US';

    public string $tier = 'basic';

    public string $status = 'active';

    public ?string $stripe_customer_id = null;

    public ?string $notes = null;

    // New profile fields
    public ?string $internal_notes = null;

    public ?string $mission = null;

    public ?string $vision = null;

    public ?string $competitors = null;

    public ?string $marketing_strategy = null;

    public bool $generating_strategy = false;

    public string $tab = 'overview';

    // Password change fields
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public bool $showPasswordModal = false;
    public array $selectedServices = [];

    // US States
    public static array $usStates = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
        'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
        'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
        'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
        'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
        'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
        'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
        'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
        'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
        'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
    ];

    // Countries
    public static array $countries = [
        'US' => 'United States',
        'CA' => 'Canada',
        'GB' => 'United Kingdom',
        'AU' => 'Australia',
        'DE' => 'Germany',
        'FR' => 'France',
        'ES' => 'Spain',
        'IT' => 'Italy',
        'NL' => 'Netherlands',
        'BE' => 'Belgium',
        'CH' => 'Switzerland',
        'AT' => 'Austria',
        'SE' => 'Sweden',
        'NO' => 'Norway',
        'DK' => 'Denmark',
        'FI' => 'Finland',
        'IE' => 'Ireland',
        'NZ' => 'New Zealand',
        'SG' => 'Singapore',
        'HK' => 'Hong Kong',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'MX' => 'Mexico',
        'BR' => 'Brazil',
        'AR' => 'Argentina',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'IN' => 'India',
        'PH' => 'Philippines',
        'ZA' => 'South Africa',
        'AE' => 'United Arab Emirates',
        'SA' => 'Saudi Arabia',
        'IL' => 'Israel',
        'PL' => 'Poland',
        'CZ' => 'Czech Republic',
        'PT' => 'Portugal',
        'GR' => 'Greece',
        'RO' => 'Romania',
        'HU' => 'Hungary',
        'OTHER' => 'Other',
    ];

    // Phone formats by country
    public static array $phoneFormats = [
        'US' => ['placeholder' => '(555) 123-4567', 'pattern' => '\\(\\d{3}\\) \\d{3}-\\d{4}'],
        'CA' => ['placeholder' => '(555) 123-4567', 'pattern' => '\\(\\d{3}\\) \\d{3}-\\d{4}'],
        'GB' => ['placeholder' => '07911 123456', 'pattern' => '\\d{5} \\d{6}'],
        'AU' => ['placeholder' => '0412 345 678', 'pattern' => '\\d{4} \\d{3} \\d{3}'],
        'DE' => ['placeholder' => '0151 12345678', 'pattern' => '\\d{4} \\d{8}'],
        'FR' => ['placeholder' => '06 12 34 56 78', 'pattern' => '\\d{2} \\d{2} \\d{2} \\d{2} \\d{2}'],
    ];

    public function updatedCountry(): void
    {
        // Reset state when country changes if not US
        if ($this->country !== 'US') {
            $this->state = null;
        }
    }

    public function getPhoneFormatProperty(): array
    {
        return self::$phoneFormats[$this->country] ?? ['placeholder' => 'Phone number', 'pattern' => '.*'];
    }

    public function mount(Client $client): void
    {
        $this->client = $client;
        $this->primaryUser = $client->users()->orderBy('id')->first();

        $this->company_name = $client->company_name;
        $this->contact_name = $client->contact_name;
        $this->email = $client->email;
        $this->phone = $client->phone;
        $this->address = $client->address;
        $this->city = $client->city;
        $this->state = $client->state;
        $this->zip_code = $client->zip_code;
        $this->country = $client->country ?? 'US';
        $this->tier = $client->tier ?? 'basic';
        $this->status = $client->status ?? 'active';
        $this->stripe_customer_id = $client->stripe_customer_id;
        $this->notes = $client->notes;
        $this->internal_notes = $client->internal_notes;
        $this->mission = $client->mission;
        $this->vision = $client->vision;
        $this->competitors = $client->competitors;
        $this->marketing_strategy = $client->marketing_strategy;
        $this->selectedServices = $client->enabled_features ?? [];
    }

    protected function rules(): array
    {
        $availableFeatures = array_keys(config('features.available', []));

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($this->client->id),
                Rule::unique('users', 'email')->ignore($this->primaryUser?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:2'],
            'tier' => ['required', Rule::in(['basic', 'standard', 'premium', 'enterprise'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending', 'suspended'])],
            'stripe_customer_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'mission' => ['nullable', 'string', 'max:2000'],
            'vision' => ['nullable', 'string', 'max:2000'],
            'competitors' => ['nullable', 'string', 'max:2000'],
            'marketing_strategy' => ['nullable', 'string'],
            'tab' => ['nullable', 'string'],
            'selectedServices' => ['array'],
            'selectedServices.*' => [Rule::in($availableFeatures)],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'tab') {
            return;
        }
        $this->validateOnly($property);
    }

    public function generateMarketingStrategy(): void
    {
        $this->generating_strategy = true;

        try {
            $ai = app(AIProviderManager::class);

            $context = "Company: {$this->company_name}\n";
            if ($this->mission) {
                $context .= "Mission: {$this->mission}\n";
            }
            if ($this->vision) {
                $context .= "Vision: {$this->vision}\n";
            }
            if ($this->competitors) {
                $context .= "Known Competitors: {$this->competitors}\n";
            }
            if ($this->city || $this->state) {
                $context .= "Location: " . implode(', ', array_filter([$this->city, $this->state])) . "\n";
            }

            $prompt = <<<PROMPT
Based on the following client information, create a comprehensive marketing strategy:

{$context}

Please provide a detailed marketing strategy that includes:

1. **Executive Summary** - Brief overview of the recommended approach
2. **Target Audience** - Who should this company focus on reaching
3. **Unique Value Proposition** - What makes this company stand out
4. **Marketing Channels** - Recommended channels (digital, social, traditional)
5. **Content Strategy** - Types of content to create and share
6. **Competitive Positioning** - How to differentiate from competitors
7. **Key Metrics & KPIs** - How to measure success
8. **Quick Wins** - Immediate actions that can show results
9. **Long-term Initiatives** - Strategic goals for 6-12 months

Format the response in clean HTML with headers and bullet points for easy reading.
PROMPT;

            $response = $ai->chat([
                ['role' => 'system', 'content' => 'You are an expert marketing strategist. Provide actionable, specific marketing strategies tailored to the client\'s business.'],
                ['role' => 'user', 'content' => $prompt],
            ], [
                'max_tokens' => 2000,
            ]);

            $this->marketing_strategy = $response;
            
            // Save immediately to client
            $this->client->update([
                'marketing_strategy' => $response,
                'marketing_strategy_generated_at' => now(),
            ]);
            
            session()->flash('success', 'Marketing strategy generated successfully.');
        } catch (\Exception $e) {
            $this->addError('marketing_strategy', 'Failed to generate strategy: ' . $e->getMessage());
        } finally {
            $this->generating_strategy = false;
        }
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'mission' => ['nullable', 'string', 'max:2000'],
            'vision' => ['nullable', 'string', 'max:2000'],
            'competitors' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $this->client->update([
            'mission' => $validated['mission'],
            'vision' => $validated['vision'],
            'competitors' => $validated['competitors'],
            'internal_notes' => $validated['internal_notes'],
        ]);

        session()->flash('success', 'Business profile updated.');
    }

    public function save()
    {
        $data = $this->validate();

        $this->client->update([
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip_code' => $data['zip_code'],
            'country' => $data['country'] ?: 'US',
            'tier' => $data['tier'],
            'status' => $data['status'],
            'stripe_customer_id' => $data['stripe_customer_id'],
            'notes' => $data['notes'],
            'internal_notes' => $data['internal_notes'],
            'mission' => $data['mission'],
            'vision' => $data['vision'],
            'competitors' => $data['competitors'],
            'marketing_strategy' => $data['marketing_strategy'],
            'enabled_features' => $data['selectedServices'],
        ]);

        // Keep primary user in sync
        $this->primaryUser = $this->client->users()->orderBy('id')->first();
        if ($this->primaryUser) {
            $this->primaryUser->update([
                'name' => $this->client->contact_name,
                'email' => $this->client->email,
                'is_active' => $this->client->status === 'active',
            ]);
        }

        session()->flash('success', 'Client updated.');

        return redirect()->route('admin.clients.show', $this->client);
    }

    public function sendPasswordReset(): void
    {
        $user = $this->primaryUser ?? $this->client->users()->orderBy('id')->first();
        if (! $user) {
            session()->flash('error', 'No linked user found.');

            return;
        }

        Password::sendResetLink(['email' => $user->email]);
        session()->flash('success', 'Password reset email sent.');
    }

    public function saveServices(): void
    {
        $availableFeatures = array_keys(config('features.available', []));
        $validated = $this->validate([
            'selectedServices' => ['array'],
            'selectedServices.*' => [Rule::in($availableFeatures)],
        ]);

        $this->client->update([
            'enabled_features' => $validated['selectedServices'],
        ]);

        session()->flash('success', 'Services updated.');
    }

    public function render()
    {
        $activities = ActivityLog::query()
            ->where('client_id', $this->client->id)
            ->with(['user'])
            ->latest()
            ->paginate(20);

        $availableServices = config('features.available', []);
        
        // Group by category while preserving original keys
        $servicesByCategory = [];
        foreach ($availableServices as $key => $service) {
            $category = $service['category'] ?? 'other';
            $servicesByCategory[$category][$key] = $service;
        }

        return view('livewire.admin.clients.edit', [
            'tiers' => ['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium', 'enterprise' => 'Enterprise'],
            'statuses' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'suspended' => 'Suspended'],
            'activities' => $activities,
            'availableServices' => $availableServices,
            'servicesByCategory' => $servicesByCategory,
            'tierFeatures' => config('features.tiers', []),
            'usStates' => self::$usStates,
            'countries' => self::$countries,
            'phoneFormat' => $this->phoneFormat,
        ])->layout('layouts.admin', ['title' => 'Edit Client']);
    }
}
