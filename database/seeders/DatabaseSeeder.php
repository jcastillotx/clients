<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always seed roles/permissions (safe + required for authorization gates).
        $this->call(RoleAndPermissionSeeder::class);

        // IMPORTANT (production safety):
        // The remaining seeders create demo/test entities (including default passwords).
        // They must never run as part of a production deploy.
        if (app()->environment('production')) {
            return;
        }

        $this->call([
            UserSeeder::class,
            ClientSeeder::class,
            RequestSeeder::class,
            InvoiceSeeder::class,
            ContractSeeder::class,
        ]);
    }
}
