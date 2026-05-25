<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketEscalation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'escalated_by',
        'escalated_to_team',
        'escalated_to_user',
        'reason',
        'notes',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
