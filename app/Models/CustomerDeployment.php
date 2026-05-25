<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDeployment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'deployment_type',
        'status',
        'software_version',
        'installed_modules',
        'integrations',
        'server_details',
        'sla_tier',
        'contract_expires_at',
        'last_updated_at',
    ];

    protected $casts = [
        'installed_modules' => 'array',
        'integrations' => 'array',
        'server_details' => 'array',
        'contract_expires_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
