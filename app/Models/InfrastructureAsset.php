<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfrastructureAsset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'provider',
        'expiry_date',
        'cost',
        'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'cost' => 'decimal:2',
    ];
}
