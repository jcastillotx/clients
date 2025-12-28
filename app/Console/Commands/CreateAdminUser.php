<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin
                            {--email= : Admin email address}
                            {--name= : Admin name}
                            {--password= : Admin password}';

    protected $description = 'Create an admin user account';

    public function handle(): int
    {
        $this->info('Creating admin user...');

        // Get user input
        $email = $this->option('email') ?: $this->ask('Email address', 'admin@kre8ivdesigns.com');
        $name = $this->option('name') ?: $this->ask('Name', 'Admin User');
        $password = $this->option('password') ?: $this->secret('Password (leave empty for "password")') ?: 'password';

        // Validate
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  ' . $error);
            }
            return self::FAILURE;
        }

        // Ensure admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
            'status' => 'active',
        ]);

        // Assign admin role
        $user->assignRole('admin');

        $this->info('');
        $this->info('✓ Admin user created successfully!');
        $this->info('');
        $this->info('Login credentials:');
        $this->info('  Email: ' . $email);
        $this->info('  Password: ' . ($this->option('password') ? '(as specified)' : $password));
        $this->info('');

        return self::SUCCESS;
    }
}
