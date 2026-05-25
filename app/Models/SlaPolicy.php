<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'tier',
        'priority',
        'first_response_mins',
        'resolution_mins',
        'business_hours',
        'is_active',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'is_active' => 'boolean',
    ];
}
