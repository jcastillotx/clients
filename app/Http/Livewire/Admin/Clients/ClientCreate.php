<?php

namespace App\Http\Livewire\Admin\Clients;

use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use App\Models\User;
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

    public bool $sendPasswordSetLink = true;

    protected function rules(): array
    {
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
            'sendPasswordSetLink' => ['boolean'],
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
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
        return view('livewire.admin.clients.create', [
            'tiers' => ['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium', 'enterprise' => 'Enterprise'],
            'statuses' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'suspended' => 'Suspended'],
        ])->layout('layouts.admin', ['title' => 'Add Client']);
    }
}
