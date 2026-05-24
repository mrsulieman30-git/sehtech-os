<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceControl extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'compliance_framework_id',
        'name',
        'description',
        'status',
        'evidence',
    ];

    public function framework()
    {
        return $this->belongsTo(ComplianceFramework::class, 'compliance_framework_id');
    }
}
