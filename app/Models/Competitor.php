<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competitor extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'product_category',
        'pricing_tier',
        'strengths',
        'weaknesses',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
