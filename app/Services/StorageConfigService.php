<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class StorageConfigService
{
    /**
     * Available storage disk types and their configuration requirements.
     */
    protected array $diskTypes = [
        'local' => [
            'name' => 'Local Disk',
            'description' => 'Store files on the local server filesystem',
            'icon' => 'server',
            'requires_config' => false,
        ],
        's3' => [
            'name' => 'Amazon S3',
            'description' => 'Amazon Web Services S3 object storage',
            'icon' => 'cloud',
            'requires_config' => true,
            'config_fields' => ['key', 'secret', 'region', 'bucket', 'endpoint', 'url'],
        ],
        'do-spaces' => [
            'name' => 'DigitalOcean Spaces',
            'description' => 'DigitalOcean Spaces object storage (S3-compatible)',
            'icon' => 'cloud',
            'requires_config' => true,
            'config_fields' => ['key', 'secret', 'region', 'bucket', 'endpoint'],
        ],
        'r2' => [
            'name' => 'Cloudflare R2',
            'description' => 'Cloudflare R2 object storage (S3-compatible)',
            'icon' => 'cloud',
            'requires_config' => true,
            'config_fields' => ['key', 'secret', 'bucket', 'endpoint'],
        ],
        'b2' => [
            'name' => 'Backblaze B2',
            'description' => 'Backblaze B2 cloud storage (S3-compatible)',
            'icon' => 'cloud',
            'requires_config' => true,
            'config_fields' => ['key', 'secret', 'region', 'bucket', 'endpoint'],
        ],
        'minio' => [
            'name' => 'MinIO',
            'description' => 'Self-hosted MinIO object storage (S3-compatible)',
            'icon' => 'server',
            'requires_config' => true,
            'config_fields' => ['key', 'secret', 'region', 'bucket', 'endpoint'],
        ],
    ];

    /**
     * Storage categories that can be configured independently.
     */
    protected array $storageCategories = [
        'attachments' => [
            'name' => 'Request Attachments',
            'description' => 'Files uploaded with service requests',
            'default_disk' => 'local',
        ],
        'documents' => [
            'name' => 'Documents',
            'description' => 'Client documents and shared files',
            'default_disk' => 'local',
        ],
        'contracts' => [
            'name' => 'Contracts',
            'description' => 'Contract PDFs and signed documents',
            'default_disk' => 'local',
        ],
        'invoices' => [
            'name' => 'Invoices',
            'description' => 'Generated invoice PDFs',
            'default_disk' => 'local',
        ],
        'reports' => [
            'name' => 'Reports',
            'description' => 'Generated report files',
            'default_disk' => 'local',
        ],
        'exports' => [
            'name' => 'Exports',
            'description' => 'Data export files',
            'default_disk' => 'local',
        ],
    ];

    /**
     * Get all available disk types.
     */
    public function getDiskTypes(): array
    {
        return $this->diskTypes;
    }

    /**
     * Get all storage categories.
     */
    public function getStorageCategories(): array
    {
        return $this->storageCategories;
    }

    /**
     * Get the currently configured disk for a category.
     */
    public function getDiskForCategory(string $category): string
    {
        $cacheKey = "storage_config.{$category}.disk";

        return Cache::remember($cacheKey, 3600, function () use ($category) {
            $setting = Setting::query()
                ->where('key', "storage.{$category}.disk")
                ->first();

            if ($setting) {
                return $setting->value;
            }

            return $this->storageCategories[$category]['default_disk'] ?? 'local';
        });
    }

    /**
     * Set the disk for a storage category.
     */
    public function setDiskForCategory(string $category, string $disk): void
    {
        if (! isset($this->storageCategories[$category])) {
            throw new \InvalidArgumentException("Invalid storage category: {$category}");
        }

        if ($disk !== 'local' && ! isset($this->diskTypes[$disk])) {
            throw new \InvalidArgumentException("Invalid disk type: {$disk}");
        }

        Setting::query()->updateOrCreate(
            ['key' => "storage.{$category}.disk"],
            ['value' => $disk]
        );

        Cache::forget("storage_config.{$category}.disk");
    }

    /**
     * Get the configuration for an external disk.
     */
    public function getExternalDiskConfig(string $diskType): array
    {
        $cacheKey = "storage_config.external.{$diskType}";

        return Cache::remember($cacheKey, 3600, function () use ($diskType) {
            $settings = Setting::query()
                ->where('key', 'like', "storage.external.{$diskType}.%")
                ->get();

            $config = [];
            foreach ($settings as $setting) {
                $key = str_replace("storage.external.{$diskType}.", '', $setting->key);
                $config[$key] = $setting->value;
            }

            return $config;
        });
    }

    /**
     * Set configuration for an external disk.
     */
    public function setExternalDiskConfig(string $diskType, array $config): void
    {
        if (! isset($this->diskTypes[$diskType])) {
            throw new \InvalidArgumentException("Invalid disk type: {$diskType}");
        }

        foreach ($config as $key => $value) {
            if ($value !== null && $value !== '') {
                Setting::query()->updateOrCreate(
                    ['key' => "storage.external.{$diskType}.{$key}"],
                    [
                        'value' => $value,
                        'is_encrypted' => in_array($key, ['secret', 'key']),
                    ]
                );
            } else {
                Setting::query()
                    ->where('key', "storage.external.{$diskType}.{$key}")
                    ->delete();
            }
        }

        Cache::forget("storage_config.external.{$diskType}");
    }

    /**
     * Test connection to an external disk.
     */
    public function testConnection(string $diskType, array $config = []): array
    {
        try {
            // Use provided config or get stored config
            if (empty($config)) {
                $config = $this->getExternalDiskConfig($diskType);
            }

            if (empty($config)) {
                return [
                    'success' => false,
                    'message' => 'No configuration found for this disk type.',
                ];
            }

            // Build disk configuration
            $diskConfig = $this->buildDiskConfig($diskType, $config);

            // Create a temporary disk instance
            Config::set("filesystems.disks.test_{$diskType}", $diskConfig);

            $disk = Storage::disk("test_{$diskType}");

            // Try to list files to test connection
            $disk->files('');

            return [
                'success' => true,
                'message' => 'Connection successful!',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build a disk configuration array for the given type.
     */
    protected function buildDiskConfig(string $diskType, array $config): array
    {
        $base = [
            'driver' => 's3',
            'throw' => false,
        ];

        switch ($diskType) {
            case 's3':
                return array_merge($base, [
                    'key' => $config['key'] ?? '',
                    'secret' => $config['secret'] ?? '',
                    'region' => $config['region'] ?? 'us-east-1',
                    'bucket' => $config['bucket'] ?? '',
                    'url' => $config['url'] ?? null,
                    'endpoint' => $config['endpoint'] ?? null,
                    'use_path_style_endpoint' => false,
                ]);

            case 'do-spaces':
                $region = $config['region'] ?? 'nyc3';
                return array_merge($base, [
                    'key' => $config['key'] ?? '',
                    'secret' => $config['secret'] ?? '',
                    'region' => $region,
                    'bucket' => $config['bucket'] ?? '',
                    'endpoint' => $config['endpoint'] ?? "https://{$region}.digitaloceanspaces.com",
                    'use_path_style_endpoint' => false,
                ]);

            case 'r2':
                return array_merge($base, [
                    'key' => $config['key'] ?? '',
                    'secret' => $config['secret'] ?? '',
                    'region' => 'auto',
                    'bucket' => $config['bucket'] ?? '',
                    'endpoint' => $config['endpoint'] ?? '',
                    'use_path_style_endpoint' => false,
                ]);

            case 'b2':
                return array_merge($base, [
                    'key' => $config['key'] ?? '',
                    'secret' => $config['secret'] ?? '',
                    'region' => $config['region'] ?? 'us-west-002',
                    'bucket' => $config['bucket'] ?? '',
                    'endpoint' => $config['endpoint'] ?? '',
                    'use_path_style_endpoint' => false,
                ]);

            case 'minio':
                return array_merge($base, [
                    'key' => $config['key'] ?? '',
                    'secret' => $config['secret'] ?? '',
                    'region' => $config['region'] ?? 'us-east-1',
                    'bucket' => $config['bucket'] ?? '',
                    'endpoint' => $config['endpoint'] ?? 'http://localhost:9000',
                    'use_path_style_endpoint' => true,
                ]);

            default:
                return $base;
        }
    }

    /**
     * Get the Storage disk instance for a category.
     */
    public function disk(string $category): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $diskName = $this->getDiskForCategory($category);

        if ($diskName === 'local') {
            return Storage::disk($category);
        }

        // For external disks, we need to configure them dynamically
        $config = $this->getExternalDiskConfig($diskName);
        $diskConfig = $this->buildDiskConfig($diskName, $config);

        // Set a unique disk name for this category's external config
        $configKey = "filesystems.disks.{$category}_external";
        Config::set($configKey, $diskConfig);

        return Storage::disk("{$category}_external");
    }

    /**
     * Get storage usage statistics for a category.
     */
    public function getStorageStats(string $category): array
    {
        try {
            $disk = Storage::disk($category);
            $files = $disk->allFiles();

            $totalSize = 0;
            $fileCount = count($files);

            foreach ($files as $file) {
                $totalSize += $disk->size($file);
            }

            return [
                'file_count' => $fileCount,
                'total_size' => $totalSize,
                'formatted_size' => $this->formatBytes($totalSize),
            ];
        } catch (\Exception $e) {
            return [
                'file_count' => 0,
                'total_size' => 0,
                'formatted_size' => '0 B',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
