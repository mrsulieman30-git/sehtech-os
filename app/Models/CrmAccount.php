<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmAccount extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'industry',
        'website',
        'city',
        'country',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function activities()
    {
        return $this->morphMany(CrmActivity::class, 'activatable')->orderBy('created_at', 'desc');
    }
}
