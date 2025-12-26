<?php

namespace App\Http\Livewire\Admin\Clients;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
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

    public string $tab = 'overview';

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
    }

    protected function rules(): array
    {
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
            'tab' => ['nullable', 'string'],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'tab') return;
        $this->validateOnly($property);
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
        if (!$user) {
            session()->flash('error', 'No linked user found.');
            return;
        }

        Password::sendResetLink(['email' => $user->email]);
        session()->flash('success', 'Password reset email sent.');
    }

    public function render()
    {
        $activities = ActivityLog::query()
            ->where('client_id', $this->client->id)
            ->with(['user'])
            ->latest()
            ->paginate(20);

        return view('livewire.admin.clients.edit', [
            'tiers' => ['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium', 'enterprise' => 'Enterprise'],
            'statuses' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'suspended' => 'Suspended'],
            'activities' => $activities,
        ])->layout('layouts.admin', ['title' => 'Edit Client']);
    }
}

