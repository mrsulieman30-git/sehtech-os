<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmContact extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'crm_account_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'whatsapp',
        'job_title',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CrmAccount::class, 'Crm_account_id');
    }

    public function activities()
    {
        return $this->morphMany(CrmActivity::class, 'activatable')->orderBy('created_at', 'desc');
    }
}
