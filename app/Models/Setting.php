<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
        'updated_by',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public const CACHE_KEY = 'settings.kv.v1';

    /**
     * @return array<string, array{value:?string,is_encrypted:bool}>
     */
    public static function allCached(): array
    {
        /** @var array<string, array{value:?string,is_encrypted:bool}> $map */
        $map = Cache::rememberForever(self::CACHE_KEY, function () {
            return self::query()
                ->get(['key', 'value', 'is_encrypted'])
                ->mapWithKeys(fn (self $s) => [
                    $s->key => [
                        'value' => $s->value,
                        'is_encrypted' => (bool) $s->is_encrypted,
                    ],
                ])
                ->all();
        });

        return $map;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $map = self::allCached();
        if (!array_key_exists($key, $map)) {
            return $default;
        }

        $raw = $map[$key]['value'];
        if ($raw === null) {
            return $default;
        }

        if ($map[$key]['is_encrypted']) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Throwable $e) {
                return $default;
            }
        }

        // All values are stored as JSON to preserve types.
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Fallback for legacy/plain values.
        return $raw;
    }

    public static function setValue(string $key, mixed $value, bool $encrypt = false, ?int $updatedBy = null): void
    {
        $payload = json_encode($value);
        if ($payload === false) {
            // As last resort, store string representation.
            $payload = json_encode((string) $value);
        }

        $stored = $encrypt ? Crypt::encryptString($payload) : $payload;

        self::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'is_encrypted' => $encrypt,
                'updated_by' => $updatedBy,
            ]
        );

        self::clearCache();
    }
}

