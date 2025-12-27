<?php

namespace App\Http\Livewire\Storage;

use App\Models\Client;
use App\Models\StorageConnection;
use App\Services\Storage\AwsS3Service;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ConnectS3 extends Component
{
    public ?int $client_id = null;

    public string $access_key_id = '';

    public string $secret_access_key = '';

    public string $region = 'us-east-1';

    public string $bucket = '';

    public string $folder_path = '';

    public bool $is_primary = true;

    public string $testMessage = '';

    public string $testError = '';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->client_id) {
            $this->client_id = $user->client_id;
        }
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'access_key_id' => ['required', 'string', 'max:255'],
            'secret_access_key' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:50'],
            'bucket' => ['required', 'string', 'max:255'],
            'folder_path' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
        ];
    }

    public function testConnection(AwsS3Service $s3): void
    {
        $this->testMessage = '';
        $this->testError = '';

        try {
            $data = $this->validate();
            $s3->connect([
                'client_id' => $data['client_id'],
                'access_key_id' => $data['access_key_id'],
                'secret_access_key' => $data['secret_access_key'],
                'region' => $data['region'],
                'bucket' => $data['bucket'],
                'folder_path' => $data['folder_path'] ?? '',
                'is_primary' => (bool) $data['is_primary'],
                'save' => false,
            ]);
            $this->testMessage = 'Connection successful.';
        } catch (\Throwable $e) {
            $this->testError = $e->getMessage();
        }
    }

    public function save(AwsS3Service $s3)
    {
        $this->testMessage = '';
        $this->testError = '';

        try {
            $data = $this->validate();

            $s3->connect([
                'client_id' => $data['client_id'],
                'access_key_id' => $data['access_key_id'],
                'secret_access_key' => $data['secret_access_key'],
                'region' => $data['region'],
                'bucket' => $data['bucket'],
                'folder_path' => $data['folder_path'] ?? '',
                'is_primary' => (bool) $data['is_primary'],
                'save' => true,
            ]);

            session()->flash('success', 'S3 connection saved.');

            $conn = StorageConnection::query()
                ->where('client_id', $data['client_id'])
                ->where('provider', 'aws_s3')
                ->first();

            if ($conn) {
                return redirect()->route('admin.storage.s3.browse', ['connection' => $conn->id]);
            }

            return redirect()->route('admin.storage.s3.connect');
        } catch (\Throwable $e) {
            $this->testError = $e->getMessage();

            return null;
        }
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() || $user?->isStaff();

        $clients = $isAdmin
            ? Client::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        $regions = [
            'us-east-1', 'us-east-2',
            'us-west-1', 'us-west-2',
            'ca-central-1',
            'eu-west-1', 'eu-west-2', 'eu-west-3',
            'eu-central-1',
            'ap-southeast-1', 'ap-southeast-2',
            'ap-northeast-1', 'ap-northeast-2',
            'ap-south-1',
            'sa-east-1',
        ];

        return view('livewire.storage.connect-s3', [
            'isAdmin' => $isAdmin,
            'clients' => $clients,
            'regions' => $regions,
        ])->layout('layouts.admin', ['title' => 'Connect AWS S3']);
    }
}
