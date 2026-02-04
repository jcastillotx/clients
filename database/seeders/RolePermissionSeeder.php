<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions (requested naming)
        $permissions = [
            // Admin shell access
            'access admin panel',

            // Clients
            'view_any_client',
            'view_client',
            'create_client',
            'update_client',
            'delete_client',

            // Requests
            'view_any_request',
            'view_request',
            'create_request',
            'update_request',
            'delete_request',
            'assign_request', // Ability to assign requests to staff

            // Leads
            'view_any_lead',
            'view_lead',
            'create_lead',
            'update_lead',
            'delete_lead',

            // Invoices
            'view_any_invoice',
            'view_invoice',
            'create_invoice',
            'update_invoice',
            'delete_invoice',
            'process_payment',

            // Contracts
            'view_any_contract',
            'view_contract',
            'create_contract',
            'update_contract',

            // Documents
            'view_any_document',
            'view_document',
            'upload_document',
            'delete_document',

            // Users & Permissions
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'manage_permissions',

            // Settings
            // (Keep both legacy + newer names; code uses "manage settings")
            'view_settings',
            'update_settings',
            'manage settings',

            // Documents (admin helpers; some UI gates on this)
            'manage documents',

            // Reporting (route middleware uses this)
            'view reports',
        ];

        // Support both the session ("web") and token ("sanctum") guards so
        // permission checks work consistently for API tokens.
        $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]);
            }
        }

        // Create roles and assign permissions

        foreach ($guards as $guard) {
            $allPermissions = Permission::query()->where('guard_name', $guard)->get();

            // super_admin - full access
            $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
            $superAdminRole->syncPermissions($allPermissions);

            // admin - delegated admin (restricted from critical system operations)
            $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
            $adminPermissions = $allPermissions->reject(function ($permission) {
                return in_array($permission->name, [
                    'delete_user',        // Can manage users but not delete them
                    'manage_permissions', // Cannot modify role permissions
                    'view_settings',      // Cannot view system settings
                    'update_settings',    // Cannot modify system settings
                ]);
            });
            $adminRole->syncPermissions($adminPermissions);

            // project_manager - can assign requests to staff, higher than staff but below admin
            $projectManagerRole = Role::firstOrCreate(['name' => 'project_manager', 'guard_name' => $guard]);
            $projectManagerRole->syncPermissions([
                'access admin panel',

                // Clients
                'view_any_client',
                'view_client',
                'create_client',
                'update_client',

                // Requests (including assign)
                'view_any_request',
                'view_request',
                'create_request',
                'update_request',
                'delete_request',
                'assign_request',

                // Leads
                'view_any_lead',
                'view_lead',
                'create_lead',
                'update_lead',

                // Invoices
                'view_any_invoice',
                'view_invoice',
                'create_invoice',
                'update_invoice',
                'process_payment',

                // Contracts
                'view_any_contract',
                'view_contract',
                'create_contract',
                'update_contract',

                // Documents
                'view_any_document',
                'view_document',
                'upload_document',
                'delete_document',

                // Users (limited)
                'view_any_user',
                'view_user',

                // Settings (limited)
                'manage settings',

                // Reporting
                'view reports',
            ]);

            // project_lead - similar to project_manager, manages project execution
            $projectLeadRole = Role::firstOrCreate(['name' => 'project_lead', 'guard_name' => $guard]);
            $projectLeadRole->syncPermissions([
                'access admin panel',

                // Clients
                'view_any_client',
                'view_client',
                'create_client',
                'update_client',

                // Requests (including assign)
                'view_any_request',
                'view_request',
                'create_request',
                'update_request',
                'delete_request',
                'assign_request',

                // Leads
                'view_any_lead',
                'view_lead',
                'create_lead',
                'update_lead',

                // Invoices
                'view_any_invoice',
                'view_invoice',
                'create_invoice',
                'update_invoice',
                'process_payment',

                // Contracts
                'view_any_contract',
                'view_contract',
                'create_contract',
                'update_contract',

                // Documents
                'view_any_document',
                'view_document',
                'upload_document',
                'delete_document',

                // Users (limited)
                'view_any_user',
                'view_user',

                // Settings (limited)
                'manage settings',

                // Reporting
                'view reports',
            ]);

            // staff - operational access (generic staff without assignment capability)
            $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);
            $staffRole->syncPermissions([
                'access admin panel',

                // Clients
                'view_any_client',
                'view_client',
                'create_client',
                'update_client',

                // Requests
                'view_any_request',
                'view_request',
                'create_request',
                'update_request',
                'delete_request',

                // Leads
                'view_any_lead',
                'view_lead',
                'create_lead',
                'update_lead',

                // Invoices
                'view_any_invoice',
                'view_invoice',
                'create_invoice',
                'update_invoice',
                'process_payment',

                // Contracts
                'view_any_contract',
                'view_contract',
                'create_contract',
                'update_contract',

                // Documents
                'view_any_document',
                'view_document',
                'upload_document',
                'delete_document',

                // Users (limited)
                'view_any_user',
                'view_user',

                // Settings (limited)
                'manage settings',

                // Reporting
                'view reports',
            ]);

            // ============================================================
            // Strategy & Business Development
            // ============================================================

            // Marketing Director/VP - senior leadership with broad access
            $marketingDirectorRole = Role::firstOrCreate(['name' => 'marketing_director', 'guard_name' => $guard]);
            $marketingDirectorRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client', 'create_client', 'update_client',
                'view_any_request', 'view_request', 'create_request', 'update_request', 'delete_request', 'assign_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice',
                'view_any_contract', 'view_contract', 'create_contract', 'update_contract',
                'view_any_document', 'view_document', 'upload_document', 'delete_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Account Manager - manages client relationships
            $accountManagerRole = Role::firstOrCreate(['name' => 'account_manager', 'guard_name' => $guard]);
            $accountManagerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client', 'create_client', 'update_client',
                'view_any_request', 'view_request', 'create_request', 'update_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'process_payment',
                'view_any_contract', 'view_contract', 'create_contract', 'update_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Business Development Manager - focuses on new business
            $bizDevRole = Role::firstOrCreate(['name' => 'business_development_manager', 'guard_name' => $guard]);
            $bizDevRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client', 'create_client', 'update_client',
                'view_any_request', 'view_request', 'create_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice', 'create_invoice',
                'view_any_contract', 'view_contract', 'create_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // ============================================================
            // Creative Team
            // ============================================================

            // Creative Director - leads creative team
            $creativeDirectorRole = Role::firstOrCreate(['name' => 'creative_director', 'guard_name' => $guard]);
            $creativeDirectorRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'create_request', 'update_request', 'assign_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document', 'delete_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Graphic Designer
            $graphicDesignerRole = Role::firstOrCreate(['name' => 'graphic_designer', 'guard_name' => $guard]);
            $graphicDesignerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Copywriter (keeping existing but renaming for consistency)
            $copywriterRole = Role::firstOrCreate(['name' => 'copywriter', 'guard_name' => $guard]);
            $copywriterRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Videographer/Photographer
            $videographerRole = Role::firstOrCreate(['name' => 'videographer_photographer', 'guard_name' => $guard]);
            $videographerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // ============================================================
            // Digital Marketing Team
            // ============================================================

            // Digital Marketing Manager
            $digitalMarketingManagerRole = Role::firstOrCreate(['name' => 'digital_marketing_manager', 'guard_name' => $guard]);
            $digitalMarketingManagerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client', 'update_client',
                'view_any_request', 'view_request', 'create_request', 'update_request', 'assign_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document', 'delete_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // SEO Specialist
            $seoSpecialistRole = Role::firstOrCreate(['name' => 'seo_specialist', 'guard_name' => $guard]);
            $seoSpecialistRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // PPC Specialist
            $ppcSpecialistRole = Role::firstOrCreate(['name' => 'ppc_specialist', 'guard_name' => $guard]);
            $ppcSpecialistRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Social Media Manager
            $socialMediaManagerRole = Role::firstOrCreate(['name' => 'social_media_manager', 'guard_name' => $guard]);
            $socialMediaManagerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'create_request', 'update_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Email Marketing Specialist
            $emailMarketingRole = Role::firstOrCreate(['name' => 'email_marketing_specialist', 'guard_name' => $guard]);
            $emailMarketingRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // ============================================================
            // Web Development & Technology
            // ============================================================

            // Web Developer (keeping 'developer' for backwards compatibility)
            $developerRole = Role::firstOrCreate(['name' => 'developer', 'guard_name' => $guard]);
            $developerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // UX/UI Designer (keeping 'designer' for backwards compatibility)
            $designerRole = Role::firstOrCreate(['name' => 'designer', 'guard_name' => $guard]);
            $designerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // CRM Manager
            $crmManagerRole = Role::firstOrCreate(['name' => 'crm_manager', 'guard_name' => $guard]);
            $crmManagerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client', 'create_client', 'update_client',
                'view_any_request', 'view_request', 'update_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // ============================================================
            // Analytics & Insights
            // ============================================================

            // Marketing Analyst
            $marketingAnalystRole = Role::firstOrCreate(['name' => 'marketing_analyst', 'guard_name' => $guard]);
            $marketingAnalystRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request',
                'view_any_lead', 'view_lead',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Data Scientist/Analyst
            $dataScientistRole = Role::firstOrCreate(['name' => 'data_scientist', 'guard_name' => $guard]);
            $dataScientistRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // ============================================================
            // Client Services & Support
            // ============================================================

            // Client Services Manager
            $clientServicesManagerRole = Role::firstOrCreate(['name' => 'client_services_manager', 'guard_name' => $guard]);
            $clientServicesManagerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client', 'create_client', 'update_client',
                'view_any_request', 'view_request', 'create_request', 'update_request', 'assign_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice',
                'view_any_contract', 'view_contract', 'create_contract', 'update_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Customer Support/Community Manager
            $customerSupportRole = Role::firstOrCreate(['name' => 'customer_support_manager', 'guard_name' => $guard]);
            $customerSupportRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client', 'update_client',
                'view_any_request', 'view_request', 'create_request', 'update_request',
                'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // ============================================================
            // Operations & Administration
            // ============================================================

            // HR Manager
            $hrManagerRole = Role::firstOrCreate(['name' => 'hr_manager', 'guard_name' => $guard]);
            $hrManagerRole->syncPermissions([
                'access admin panel',
                'view_any_user', 'view_user', 'create_user', 'update_user',
                'view_any_document', 'view_document', 'upload_document',
                'view reports',
            ]);

            // Administrative Assistant
            $adminAssistantRole = Role::firstOrCreate(['name' => 'administrative_assistant', 'guard_name' => $guard]);
            $adminAssistantRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'create_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
            ]);

            // ============================================================
            // Finance & Legal
            // ============================================================

            // Bookkeeper/Accountant
            $bookeeperRole = Role::firstOrCreate(['name' => 'bookkeeper', 'guard_name' => $guard]);
            $bookeeperRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'process_payment',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view reports',
            ]);

            // Legal Advisor
            $legalAdvisorRole = Role::firstOrCreate(['name' => 'legal_advisor', 'guard_name' => $guard]);
            $legalAdvisorRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_contract', 'view_contract', 'create_contract', 'update_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // ============================================================
            // Optional Roles (As the company scales)
            // ============================================================

            // Public Relations (PR) Manager
            $prManagerRole = Role::firstOrCreate(['name' => 'pr_manager', 'guard_name' => $guard]);
            $prManagerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'create_request', 'update_request',
                'view_any_invoice', 'view_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Event Planner
            $eventPlannerRole = Role::firstOrCreate(['name' => 'event_planner', 'guard_name' => $guard]);
            $eventPlannerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'create_request', 'update_request',
                'view_any_invoice', 'view_invoice', 'create_invoice',
                'view_any_contract', 'view_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Influencer Marketing Manager
            $influencerManagerRole = Role::firstOrCreate(['name' => 'influencer_marketing_manager', 'guard_name' => $guard]);
            $influencerManagerRole->syncPermissions([
                'access admin panel',
                'view_any_client', 'view_client',
                'view_any_request', 'view_request', 'create_request', 'update_request',
                'view_any_invoice', 'view_invoice', 'create_invoice',
                'view_any_contract', 'view_contract', 'create_contract',
                'view_any_document', 'view_document', 'upload_document',
                'view_any_user', 'view_user',
                'view reports',
            ]);

            // Client role - limited portal access
            $clientRole = Role::firstOrCreate(['name' => 'client', 'guard_name' => $guard]);
            $clientRole->syncPermissions([
                'view_client',
                'view_request',
                'create_request',
                'update_request',
                'delete_request',  // Clients can delete their own requests (with reason tracking)
                'view_contract',   // View, download, sign, and provide feedback on contracts
                'view_invoice',    // View invoices and ask questions
                'process_payment', // Process payments on invoices
                'view_document',
                'upload_document',
            ]);
        }
    }
}
