<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'organization',
        'email',
        'phone',
        'country',
        'city',
        'source',
        'status',
        'account_status',
        'product_interest',
        'budget_range',
        'priority',
        'portal_user_id',
        'assigned_to',
        'notes',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'product_interest' => 'array',
        'meta' => 'array',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function setPhoneAttribute($value)
    {
        // Enforce country code prefixing if provided raw, ensuring standard formatting
        if ($value && !str_starts_with($value, '+')) {
            $this->attributes['phone'] = '+' . ltrim($value, '0');
        } else {
            $this->attributes['phone'] = $value;
        }
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class)->orderBy('created_at', 'desc');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
