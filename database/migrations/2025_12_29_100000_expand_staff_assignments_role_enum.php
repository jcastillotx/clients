<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The expanded list of staff assignment roles.
     */
    protected array $roles = [
        'account_manager',
        'project_lead',
        'marketing_director',
        'business_development_manager',
        'creative_director',
        'graphic_designer',
        'copywriter',
        'videographer_photographer',
        'digital_marketing_manager',
        'seo_specialist',
        'ppc_specialist',
        'social_media_manager',
        'email_marketing_specialist',
        'web_developer',
        'ux_ui_designer',
        'crm_manager',
        'marketing_analyst',
        'data_scientist',
        'client_services_manager',
        'customer_support_manager',
        'project_manager',
        'hr_manager',
        'administrative_assistant',
        'bookkeeper',
        'legal_advisor',
        'pr_manager',
        'event_planner',
        'influencer_marketing_manager',
    ];

    public function up(): void
    {
        // For MySQL/MariaDB, we need to alter the enum column
        // For SQLite (testing), enums are stored as text so no change needed
        if (DB::connection()->getDriverName() === 'mysql') {
            $enumValues = implode("','", $this->roles);
            DB::statement("ALTER TABLE staff_assignments MODIFY COLUMN role ENUM('{$enumValues}') NOT NULL");
        }
    }

    public function down(): void
    {
        // Revert to original enum values
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE staff_assignments MODIFY COLUMN role ENUM('account_manager','project_lead') NOT NULL");
        }
    }
};
