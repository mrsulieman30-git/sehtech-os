<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\SupportIncident;
use App\Models\CustomerDeployment;
use App\Models\SlaPolicy;
use App\Models\TicketEscalation;
use App\Models\KbArticle;
use App\Models\SupportAuditLog;
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

        $incidents = SupportIncident::orderBy('created_at', 'desc')->take(5)->get();
        $deployments = CustomerDeployment::with('client')->take(10)->get();
        $slas = SlaPolicy::all();
        $escalations = TicketEscalation::with('ticket')->where('status', 'pending')->get();
        
        $kbArticles = KbArticle::where('access_level', 'public')->where('is_published', true)->take(10)->get();

        return response()->json([
            'tickets' => $tickets,
            'incidents' => $incidents,
            'deployments' => $deployments,
            'slas' => $slas,
            'escalations' => $escalations,
            'kb_articles' => $kbArticles,
            'metrics' => [
                'open_tickets' => $tickets->whereIn('status', ['open', 'in_progress'])->count(),
                'escalated' => $tickets->where('status', 'escalated')->count(),
                'sla_breached' => $tickets->where('sla_due_date', '<', now())->whereNotIn('status', ['resolved', 'closed'])->count(),
                'active_incidents' => $incidents->whereNotIn('status', ['resolved', 'closed'])->count(),
                'csat_score' => 94.2, // Simulated for now
                'avg_response_time' => '1h 15m',
                'avg_resolution_time' => '4h 30m',
            ]
        ]);
    }

    public function resolveTicket(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->update(['status' => 'resolved', 'resolved_at' => now()]);
        
        \App\Models\TicketReply::create([
            'ticket_id' => $id,
            'user_id' => \Illuminate\Support\Facades\Auth::id() ?? null,
            'body' => $request->resolution_note ?? 'Ticket resolved by agent.',
            'is_internal' => false
        ]);

        SupportAuditLog::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id() ?? null,
            'action' => 'resolved_ticket',
            'entity_type' => 'ticket',
            'entity_id' => $id,
        ]);
        
        return response()->json(['message' => 'Ticket resolved']);
    }

    public function escalateTicket(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->update(['status' => 'escalated', 'is_escalated' => true]);
        
        TicketEscalation::create([
            'ticket_id' => $id,
            'escalated_by' => \Illuminate\Support\Facades\Auth::id() ?? null,
            'escalated_to_team' => $request->team ?? 'devops',
            'reason' => $request->reason ?? 'Automatic SLA breach escalation',
        ]);
        
        \App\Models\TicketReply::create([
            'ticket_id' => $id,
            'user_id' => \Illuminate\Support\Facades\Auth::id() ?? null,
            'body' => 'Ticket escalated to higher support tier.',
            'is_internal' => true
        ]);

        SupportAuditLog::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id() ?? null,
            'action' => 'escalated_ticket',
            'entity_type' => 'ticket',
            'entity_id' => $id,
        ]);
        
        return response()->json(['message' => 'Ticket escalated']);
    }
}
