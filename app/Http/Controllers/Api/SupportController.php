<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function getDashboard(Request $request)
    {
        $tickets = Ticket::with('client:id,first_name,last_name,organization')
            ->withCount('replies')
            ->orderByRaw("CASE priority WHEN 'p0' THEN 1 WHEN 'p1' THEN 2 WHEN 'p2' THEN 3 WHEN 'p3' THEN 4 END")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'tickets' => $tickets,
            'metrics' => [
                'open_tickets' => $tickets->whereIn('status', ['open', 'in_progress'])->count(),
                'escalated' => $tickets->where('status', 'escalated')->count(),
                'sla_breached' => $tickets->where('sla_due_date', '<', now())->whereNotIn('status', ['resolved', 'closed'])->count(),
            ]
        ]);
    }

    public function resolveTicket(Request $request, $id)
    {
        $ticket = \App\Models\Ticket::findOrFail($id);
        $ticket->update(['status' => 'resolved']);
        
        \App\Models\TicketReply::create([
            'ticket_id' => $id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'body' => $request->resolution_note ?? 'Ticket resolved by agent.',
            'is_internal' => false
        ]);
        
        return response()->json(['message' => 'Ticket resolved']);
    }

    public function escalateTicket(Request $request, $id)
    {
        $ticket = \App\Models\Ticket::findOrFail($id);
        $ticket->update(['status' => 'escalated']);
        
        \App\Models\TicketReply::create([
            'ticket_id' => $id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'body' => 'Ticket automatically escalated to higher support tier.',
            'is_internal' => true
        ]);
        
        return response()->json(['message' => 'Ticket escalated']);
    }
}
