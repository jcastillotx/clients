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
                Rule::unique('clients', 'email'),
                Rule::unique('users', 'email'),
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
            'sendPasswordSetLink' => ['boolean'],
            'selectedServices' => ['array'],
            'selectedServices.*' => [Rule::in($availableFeatures)],
        ];
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
        $data = $this->validate();

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

        // Create the primary client user
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
            'name' => $client->contact_name,
            'email' => $client->email,
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

        session()->flash('success', 'Client created. Welcome email sent.');

        return redirect()->route('admin.clients.show', $client);
    }

    public function render()
    {
        $availableServices = config('features.available', []);
        $servicesByCategory = collect($availableServices)->groupBy('category');

        return view('livewire.admin.clients.create', [
            'tiers' => ['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium', 'enterprise' => 'Enterprise'],
            'statuses' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'suspended' => 'Suspended'],
            'availableServices' => $availableServices,
            'servicesByCategory' => $servicesByCategory,
            'tierFeatures' => config('features.tiers', []),
        ])->layout('layouts.admin', ['title' => 'Add Client']);
    }
}
