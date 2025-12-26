<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    private const CACHE_KEY = 'system_settings.all.v1';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()
                ->get()
                ->mapWithKeys(function (Setting $s) {
                    return [$s->key => $s->decoded()];
                })
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    public function getMany(array $defaultsByKey): array
    {
        $all = $this->all();
        $out = [];
        foreach ($defaultsByKey as $key => $default) {
            $out[$key] = $all[$key] ?? $default;
        }
        return $out;
    }

    public function set(string $key, mixed $value, ?string $group = null, bool $encrypt = false): void
    {
        $encoded = Setting::encodeValue($value);
        $stored = $encrypt ? Crypt::encryptString($encoded) : $encoded;

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'is_encrypted' => $encrypt,
                'group' => $group,
                'updated_by' => Auth::id(),
            ]
        );

        $this->forgetCache();
    }

    public function setMany(array $valuesByKey, ?string $group = null, array $encryptedKeys = []): void
    {
        $encrypted = array_fill_keys($encryptedKeys, true);

        foreach ($valuesByKey as $key => $value) {
            $this->set((string) $key, $value, $group, isset($encrypted[(string) $key]));
        }
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

