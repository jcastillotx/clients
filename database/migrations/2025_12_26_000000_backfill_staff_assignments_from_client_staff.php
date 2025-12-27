<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_assignments') || ! Schema::hasTable('client_staff')) {
            return;
        }

        $existing = DB::table('staff_assignments')->count();
        if ($existing > 0) {
            return;
        }

        $rows = DB::table('client_staff')->get(['client_id', 'user_id', 'relationship', 'created_at']);

        foreach ($rows as $r) {
            $role = in_array($r->relationship, ['account_manager', 'project_lead'], true)
                ? $r->relationship
                : 'account_manager';

            DB::table('staff_assignments')->insertOrIgnore([
                'staff_user_id' => $r->user_id,
                'client_id' => $r->client_id,
                'role' => $role,
                'created_at' => $r->created_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        // no-op (data migration)
    }
};
