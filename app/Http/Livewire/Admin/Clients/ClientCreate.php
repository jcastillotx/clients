<?php

namespace App\Http\Livewire\Admin\Clients;

use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use App\Models\User;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ClientCreate extends Component
{
    // Mode: 'new' or 'existing'
    public string $mode = 'new';
    
    // For existing client selection
    public ?int $existing_client_id = null;
    public string $clientSearch = '';

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

    public bool $sendPasswordSetLink = true;

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

    public function updatedMode(): void
    {
        // Reset fields when switching modes
        if ($this->mode === 'existing') {
            $this->resetNewClientFields();
        } else {
            $this->existing_client_id = null;
            $this->clientSearch = '';
        }
    }

    public function selectExistingClient(int $clientId): void
    {
        $client = Client::find($clientId);
        if ($client) {
            $this->existing_client_id = $client->id;
            $this->clientSearch = $client->company_name;
            
            // Pre-fill form with client data
            $this->company_name = $client->company_name;
            $this->contact_name = $client->contact_name ?? '';
            $this->email = ''; // Email should be new for the user
            $this->phone = $client->phone;
            $this->address = $client->address;
            $this->city = $client->city;
            $this->state = $client->state;
            $this->zip_code = $client->zip_code;
            $this->country = $client->country ?? 'US';
            $this->tier = $client->tier ?? 'basic';
            $this->status = $client->status ?? 'active';
        }
    }

    public function clearClientSelection(): void
    {
        $this->existing_client_id = null;
        $this->clientSearch = '';
    }

    protected function resetNewClientFields(): void
    {
        $this->company_name = '';
        $this->contact_name = '';
        $this->email = '';
        $this->phone = null;
        $this->address = null;
        $this->city = null;
        $this->state = null;
        $this->zip_code = null;
        $this->country = 'US';
        $this->tier = 'basic';
        $this->status = 'active';
    }

    protected function rules(): array
    {
        $availableFeatures = array_keys(config('features.available', []));

        // Base rules for the user (always required)
        $rules = [
            'mode' => ['required', Rule::in(['new', 'existing'])],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'), // User email must always be unique
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'tier' => ['required', Rule::in(['basic', 'standard', 'premium', 'enterprise'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending', 'suspended'])],
            'sendPasswordSetLink' => ['boolean'],
        ];

        // For existing client mode, require existing_client_id
        if ($this->mode === 'existing') {
            $rules['existing_client_id'] = ['required', 'exists:clients,id'];
            $rules['company_name'] = ['nullable', 'string', 'max:255'];
        } else {
            // For new client mode, require all client fields
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['email'][] = Rule::unique('clients', 'email'); // Client email must be unique for new clients
            $rules['address'] = ['nullable', 'string', 'max:255'];
            $rules['city'] = ['nullable', 'string', 'max:100'];
            $rules['state'] = ['nullable', 'string', 'max:100'];
            $rules['zip_code'] = ['nullable', 'string', 'max:30'];
            $rules['country'] = ['nullable', 'string', 'max:5'];
            $rules['stripe_customer_id'] = ['nullable', 'string', 'max:255'];
            $rules['notes'] = ['nullable', 'string'];
            $rules['internal_notes'] = ['nullable', 'string'];
            $rules['mission'] = ['nullable', 'string', 'max:2000'];
            $rules['vision'] = ['nullable', 'string', 'max:2000'];
            $rules['competitors'] = ['nullable', 'string', 'max:2000'];
            $rules['marketing_strategy'] = ['nullable', 'string'];
            $rules['selectedServices'] = ['array'];
            $rules['selectedServices.*'] = [Rule::in($availableFeatures)];
        }

        return $rules;
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function generateMarketingStrategy(): void
    {
        if (empty($this->company_name)) {
            $this->addError('marketing_strategy', 'Please enter a company name first.');
            return;
        }

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
            if ($this->address || $this->city || $this->state) {
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
        } catch (\Exception $e) {
            $this->addError('marketing_strategy', 'Failed to generate strategy: ' . $e->getMessage());
        } finally {
            $this->generating_strategy = false;
        }
    }

    public function save()
    {
        try {
            $data = $this->validate();

            // Handle existing client mode
            if ($this->mode === 'existing' && $this->existing_client_id) {
                $client = Client::findOrFail($this->existing_client_id);
                
                // Update client with any additional info if needed
                $client->update([
                    'contact_name' => $data['contact_name'],
                    'phone' => $data['phone'] ?? $client->phone,
                    'tier' => $data['tier'],
                    'status' => $data['status'],
                ]);
            } else {
                // Create new client
                $client = Client::create([
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
                    'marketing_strategy_generated_at' => $data['marketing_strategy'] ? now() : null,
                    'enabled_features' => $data['selectedServices'],
                ]);
            }

            // Create the client user
            $temporaryPassword = null;
            $passwordSetLinkSent = (bool) $this->sendPasswordSetLink;

            if ($passwordSetLinkSent) {
                // Set an unknown strong password and send a reset link so the client can set their own.
                $temporaryPassword = Str::password(32);
            } else {
                // Provide a temporary password in the welcome email.
                $temporaryPassword = Str::password(16);
            }

            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => $temporaryPassword, // hashed by cast
                'client_id' => $client->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('client');

            if ($passwordSetLinkSent) {
                Password::sendResetLink(['email' => $user->email]);
            }

            Mail::to($user->email)->queue(new ClientWelcomeMail(
                user: $user,
                portalUrl: route('login'),
                passwordSetLinkSent: $passwordSetLinkSent,
                temporaryPassword: $passwordSetLinkSent ? null : $temporaryPassword
            ));

            $message = $this->mode === 'existing' 
                ? 'New user added to existing client. Welcome email has been sent to ' . $data['email']
                : 'Client created successfully! Welcome email has been sent to ' . $data['email'];

            session()->flash('success', $message);

            return redirect()->route('admin.clients.show', $client);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so Livewire handles them
            throw $e;
        } catch (\Exception $e) {
            // Log the error and show a user-friendly message
            \Log::error('Client creation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'company_name' => $this->company_name,
                'email' => $this->email,
            ]);
            
            session()->flash('error', 'Failed to create client: ' . $e->getMessage());
        }
    }

    public function getExistingClientsProperty()
    {
        if (empty($this->clientSearch) || strlen($this->clientSearch) < 2) {
            return collect();
        }

        return Client::query()
            ->where('company_name', 'like', '%' . $this->clientSearch . '%')
            ->orWhere('contact_name', 'like', '%' . $this->clientSearch . '%')
            ->orWhere('email', 'like', '%' . $this->clientSearch . '%')
            ->orderBy('company_name')
            ->limit(10)
            ->get(['id', 'company_name', 'contact_name', 'email']);
    }

    public function getPhoneFormatProperty(): array
    {
        return self::$phoneFormats[$this->country] ?? ['placeholder' => 'Phone number', 'pattern' => '.*'];
    }

    public function render()
    {
        $availableServices = config('features.available', []);
        
        // Group by category while preserving original keys
        $servicesByCategory = [];
        foreach ($availableServices as $key => $service) {
            $category = $service['category'] ?? 'other';
            $servicesByCategory[$category][$key] = $service;
        }

        return view('livewire.admin.clients.create', [
            'tiers' => ['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium', 'enterprise' => 'Enterprise'],
            'statuses' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'suspended' => 'Suspended'],
            'availableServices' => $availableServices,
            'servicesByCategory' => $servicesByCategory,
            'tierFeatures' => config('features.tiers', []),
            'usStates' => self::$usStates,
            'countries' => self::$countries,
            'existingClients' => $this->existingClients,
            'phoneFormat' => $this->phoneFormat,
        ])->layout('layouts.admin', ['title' => 'Add Client']);
    }
}
