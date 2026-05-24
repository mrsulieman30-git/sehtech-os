<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDeal extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'crm_account_id',
        'title',
        'description',
        'value',
        'stage',
        'priority',
        'assigned_to',
        'expected_close_date',
        'requirements',
        'payment_type',
        'recurring_frequency',
        'recurring_amount',
        'collection_date',
        'contract_file_path',
        'legal_contract_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close_date' => 'date',
        'recurring_amount' => 'decimal:2',
        'collection_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CrmAccount::class, 'crm_account_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities()
    {
        return $this->morphMany(CrmActivity::class, 'activatable')->orderBy('created_at', 'desc');
    }
}
