<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    protected $table = 'activity_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Custom convenience columns (in addition to Spatie's causer polymorph)
        'user_id',
        'client_id',
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client associated with the activity.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the subject of the activity.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer of the activity.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log an activity.
     */
    public static function log(
        string $description,
        ?Model $subject = null,
        ?array $properties = null,
        ?string $event = null,
        string $logName = 'default'
    ): static {
        $user = auth()->user();
        $request = null;
        try {
            $request = request();
        } catch (\Throwable $e) {
            // ignore
        }

        return static::create([
            'user_id' => $user?->id,
            'client_id' => $user?->client_id,
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => $properties,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Get old values from properties.
     */
    public function getOldAttribute(): array
    {
        return $this->properties['old'] ?? [];
    }

    /**
     * Get new values from properties.
     */
    public function getNewAttribute(): array
    {
        return $this->properties['new'] ?? [];
    }

    /**
     * Get changes from properties.
     */
    public function getChangesAttribute(): Collection
    {
        $old = $this->old;
        $new = $this->new;
        $changes = [];

        foreach ($new as $key => $value) {
            if (! isset($old[$key]) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return collect($changes);
    }

    // Note: Spatie's base Activity model already provides useful scopes like scopeInLog().
}
