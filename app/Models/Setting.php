<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
        'group',
        'updated_by',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function decoded(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        $raw = $this->value;
        if ($this->is_encrypted) {
            $raw = Crypt::decryptString($raw);
        }

        // We always store JSON (including scalars) so json_decode is safe.
        return json_decode($raw, true);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();
        if (! $row) {
            return $default;
        }

        $val = $row->decoded();

        return $val === null ? $default : $val;
    }

    public static function setValue(string $key, mixed $value, bool $encrypt = false, ?int $updatedBy = null, ?string $group = null): self
    {
        $encoded = static::encodeValue($value);
        $isEncrypted = $encrypt === true;
        $stored = $isEncrypted ? Crypt::encryptString($encoded) : $encoded;

        /** @var self $row */
        $row = static::query()->firstOrNew(['key' => $key]);
        $row->value = $stored;
        $row->is_encrypted = $isEncrypted;
        if ($group !== null) {
            $row->group = $group;
        }
        if ($updatedBy !== null) {
            $row->updated_by = $updatedBy;
        }
        $row->save();

        return $row;
    }

    public static function encodeValue(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
