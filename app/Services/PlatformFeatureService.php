<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class PlatformFeatureService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Cache key prefix.
     */
    protected const CACHE_PREFIX = 'platform_feature:';

    /**
     * Available platform modules that can be toggled.
     *
     * @var array<string, array{name: string, description: string, category: string}>
     */
    public static array $modules = [
        // Core Modules
        'contracts' => [
            'name' => 'Contracts',
            'description' => 'Contract management, signing, and AI generation',
            'category' => 'core',
        ],
        'invoices' => [
            'name' => 'Invoices & Payments',
            'description' => 'Invoice creation, payments, and recurring billing',
            'category' => 'core',
        ],
        'service_requests' => [
            'name' => 'Service Requests',
            'description' => 'Client service request submission and tracking',
            'category' => 'core',
        ],
        'documents' => [
            'name' => 'Documents',
            'description' => 'Document management and file storage',
            'category' => 'core',
        ],

        // Communication
        'messaging' => [
            'name' => 'Messaging',
            'description' => 'Internal messaging and communication hub',
            'category' => 'communication',
        ],
        'meetings' => [
            'name' => 'Meetings',
            'description' => 'Meeting scheduling and management',
            'category' => 'communication',
        ],

        // Reports & Analytics
        'reporting' => [
            'name' => 'Reporting',
            'description' => 'Reports, analytics, and dashboards',
            'category' => 'analytics',
        ],
        'client_analytics' => [
            'name' => 'Client Analytics',
            'description' => 'Client-facing analytics and insights',
            'category' => 'analytics',
        ],

        // Projects
        'projects' => [
            'name' => 'Projects',
            'description' => 'Project management, tasks, and time tracking',
            'category' => 'projects',
        ],
        'time_tracking' => [
            'name' => 'Time Tracking',
            'description' => 'Time entries and approvals',
            'category' => 'projects',
        ],

        // AI Features
        'ai_assistant' => [
            'name' => 'AI Assistant',
            'description' => 'AI-powered chat and assistance',
            'category' => 'ai',
        ],
        'ai_document_analysis' => [
            'name' => 'AI Document Analysis',
            'description' => 'AI-powered document analysis and insights',
            'category' => 'ai',
        ],
        'ai_contract_generation' => [
            'name' => 'AI Contract Generation',
            'description' => 'Generate contracts using AI',
            'category' => 'ai',
        ],

        // Marketing & Growth
        'proposals' => [
            'name' => 'Proposals',
            'description' => 'Proposal builder and analytics',
            'category' => 'marketing',
        ],
        'brand_monitoring' => [
            'name' => 'Brand Monitoring',
            'description' => 'Track brand mentions and sentiment',
            'category' => 'marketing',
        ],
        'social_media' => [
            'name' => 'Social Media',
            'description' => 'Social media management and scheduling',
            'category' => 'marketing',
        ],

        // Account Management
        'account_management' => [
            'name' => 'Account Management',
            'description' => 'Account health, QBRs, renewals, and upsells',
            'category' => 'account',
        ],
        'partners' => [
            'name' => 'Partners & Referrals',
            'description' => 'Partner and referral management',
            'category' => 'account',
        ],

        // Feedback & Surveys
        'feedback' => [
            'name' => 'Feedback & Surveys',
            'description' => 'Surveys and testimonial collection',
            'category' => 'engagement',
        ],

        // Advanced
        'webhooks' => [
            'name' => 'Webhooks',
            'description' => 'Webhook endpoints and integrations',
            'category' => 'advanced',
        ],
        'automation' => [
            'name' => 'Automation',
            'description' => 'Automation rules and workflows',
            'category' => 'advanced',
        ],
        'storage_integrations' => [
            'name' => 'Cloud Storage',
            'description' => 'Google Drive, Dropbox, S3 integrations',
            'category' => 'advanced',
        ],
    ];

    /**
     * Get all platform modules with their current status.
     *
     * @return array<string, array{name: string, description: string, category: string, enabled: bool}>
     */
    public function getAll(): array
    {
        $result = [];

        foreach (static::$modules as $key => $module) {
            $result[$key] = array_merge($module, [
                'enabled' => $this->isEnabled($key),
            ]);
        }

        return $result;
    }

    /**
     * Get modules grouped by category.
     *
     * @return array<string, array<string, array{name: string, description: string, category: string, enabled: bool}>>
     */
    public function getGroupedByCategory(): array
    {
        $all = $this->getAll();
        $grouped = [];

        foreach ($all as $key => $module) {
            $category = $module['category'];
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$key] = $module;
        }

        return $grouped;
    }

    /**
     * Check if a platform feature/module is enabled.
     */
    public function isEnabled(string $module): bool
    {
        $cacheKey = static::CACHE_PREFIX . $module;

        return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($module) {
            // Default to enabled for backward compatibility
            return (bool) Setting::getValue("platform.modules.{$module}", true);
        });
    }

    /**
     * Check if multiple modules are all enabled.
     *
     * @param  array<string>  $modules
     */
    public function allEnabled(array $modules): bool
    {
        foreach ($modules as $module) {
            if (! $this->isEnabled($module)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the given modules are enabled.
     *
     * @param  array<string>  $modules
     */
    public function anyEnabled(array $modules): bool
    {
        foreach ($modules as $module) {
            if ($this->isEnabled($module)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enable a platform module.
     */
    public function enable(string $module): void
    {
        Setting::setValue("platform.modules.{$module}", true, false, auth()->id(), 'platform');
        $this->clearCache($module);
    }

    /**
     * Disable a platform module.
     */
    public function disable(string $module): void
    {
        Setting::setValue("platform.modules.{$module}", false, false, auth()->id(), 'platform');
        $this->clearCache($module);
    }

    /**
     * Toggle a platform module.
     */
    public function toggle(string $module): bool
    {
        $newState = ! $this->isEnabled($module);

        if ($newState) {
            $this->enable($module);
        } else {
            $this->disable($module);
        }

        return $newState;
    }

    /**
     * Set multiple modules at once.
     *
     * @param  array<string, bool>  $modules
     */
    public function setMany(array $modules): void
    {
        foreach ($modules as $module => $enabled) {
            if ($enabled) {
                $this->enable($module);
            } else {
                $this->disable($module);
            }
        }
    }

    /**
     * Clear the cache for a specific module.
     */
    public function clearCache(string $module): void
    {
        Cache::forget(static::CACHE_PREFIX . $module);
    }

    /**
     * Clear all platform feature caches.
     */
    public function clearAllCache(): void
    {
        foreach (array_keys(static::$modules) as $module) {
            $this->clearCache($module);
        }
    }

    /**
     * Get category labels.
     *
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'core' => 'Core Modules',
            'communication' => 'Communication',
            'analytics' => 'Reports & Analytics',
            'projects' => 'Projects & Time',
            'ai' => 'AI Features',
            'marketing' => 'Marketing & Growth',
            'account' => 'Account Management',
            'engagement' => 'Feedback & Engagement',
            'advanced' => 'Advanced Features',
        ];
    }
}
