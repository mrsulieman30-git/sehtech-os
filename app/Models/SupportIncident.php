<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportIncident extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'incident_number',
        'title',
        'description',
        'type',
        'severity',
        'status',
        'lead_engineer_id',
        'started_at',
        'resolved_at',
        'root_cause',
        'affected_services',
        'mitigation_steps',
        'postmortem',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'affected_services' => 'array',
        'mitigation_steps' => 'array',
    ];
}
