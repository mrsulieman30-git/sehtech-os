<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_bills';

    protected $fillable = [
        'bill_number',
        'vendor_name',
        'category',
        'amount',
        'due_date',
        'payment_date',
        'status',
        'notes',
        'created_by',
        'is_recurring',
        'recurring_frequency',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
