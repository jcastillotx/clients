<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Keep a single source of truth for roles/permissions
        $this->call(RolePermissionSeeder::class);
    }
}
