<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntityAccessGrant extends Model
{
    protected $fillable = [
        'entity_id',
        'entity_type',
        'user_id',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a given entity has any access restrictions set.
     */
    public static function hasRestrictions(string $entityType, string $entityId): bool
    {
        return static::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->exists();
    }

    /**
     * Check if a user is allowed to access a restricted entity.
     */
    public static function isUserAllowed(string $entityType, string $entityId, string $userId): bool
    {
        // If no restrictions exist, everyone can access
        if (!static::hasRestrictions($entityType, $entityId)) {
            return true;
        }

        return static::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('user_id', $userId)
            ->exists();
    }
}
