<?php

namespace App\Models\Concerns;

use Illuminate\Support\Collection;

trait HasFeatures
{
    /**
     * Check if client has access to a specific feature
     *
     * @param string $feature Feature key (e.g., 'brand_monitoring', 'ai_insights')
     * @return bool
     */
    public function hasFeature(string $feature): bool
    {
        // If client is inactive, no features
        if (!$this->isActive()) {
            return false;
        }

        // Check explicit feature flags first (highest priority)
        $enabledFeatures = $this->enabled_features ?? [];
        if (is_array($enabledFeatures)) {
            // Explicit deny takes precedence
            if (in_array("-{$feature}", $enabledFeatures)) {
                return false;
            }
            // Explicit enable
            if (in_array($feature, $enabledFeatures)) {
                return true;
            }
        }

        // Check active contract features
        $activeContract = $this->contracts()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();

        if ($activeContract && isset($activeContract->meta['features'])) {
            if (in_array($feature, $activeContract->meta['features'])) {
                return true;
            }
        }

        // Fallback to tier-based features
        return $this->getTierFeatures()->contains($feature);
    }

    /**
     * Check if client has ALL specified features
     *
     * @param array $features
     * @return bool
     */
    public function hasAllFeatures(array $features): bool
    {
        foreach ($features as $feature) {
            if (!$this->hasFeature($feature)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if client has ANY of the specified features
     *
     * @param array $features
     * @return bool
     */
    public function hasAnyFeature(array $features): bool
    {
        foreach ($features as $feature) {
            if ($this->hasFeature($feature)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all features available to this client
     *
     * @return Collection
     */
    public function getAvailableFeatures(): Collection
    {
        $features = collect($this->getTierFeatures());

        // Add contract features
        $activeContract = $this->contracts()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();

        if ($activeContract && isset($activeContract->meta['features'])) {
            $features = $features->merge($activeContract->meta['features']);
        }

        // Add explicit features
        if ($this->enabled_features) {
            foreach ($this->enabled_features as $feature) {
                if (!str_starts_with($feature, '-')) {
                    $features->push($feature);
                } else {
                    // Remove denied features
                    $features = $features->reject(fn($f) => $f === substr($feature, 1));
                }
            }
        }

        return $features->unique();
    }

    /**
     * Enable a feature for this client
     *
     * @param string $feature
     * @return void
     */
    public function enableFeature(string $feature): void
    {
        $features = $this->enabled_features ?? [];

        // Remove deny if exists
        $features = array_filter($features, fn($f) => $f !== "-{$feature}");

        // Add feature if not already present
        if (!in_array($feature, $features)) {
            $features[] = $feature;
        }

        $this->update(['enabled_features' => $features]);
    }

    /**
     * Disable a feature for this client
     *
     * @param string $feature
     * @return void
     */
    public function disableFeature(string $feature): void
    {
        $features = $this->enabled_features ?? [];

        // Remove feature if present
        $features = array_filter($features, fn($f) => $f !== $feature);

        // Add explicit deny
        if (!in_array("-{$feature}", $features)) {
            $features[] = "-{$feature}";
        }

        $this->update(['enabled_features' => $features]);
    }

    /**
     * Get features based on client tier
     *
     * @return array
     */
    protected function getTierFeatures(): array
    {
        $tier = $this->tier ?? 'basic';

        return config("features.tiers.{$tier}",
            config('features.tiers.basic', [])
        );
    }

    /**
     * Check if client has upgraded tier (premium or higher)
     *
     * @return bool
     */
    public function isPremiumTier(): bool
    {
        return in_array($this->tier, ['premium', 'enterprise', 'platinum']);
    }

    /**
     * Get feature usage limit
     *
     * @param string $feature
     * @param string $limitType (e.g., 'monthly_requests', 'storage_gb')
     * @return int|null
     */
    public function getFeatureLimit(string $feature, string $limitType): ?int
    {
        $tier = $this->tier ?? 'basic';

        return config("features.limits.{$tier}.{$feature}.{$limitType}");
    }
}
