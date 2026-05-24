<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index()
    {
        $clientId = Auth::guard('portal')->user()->client_id;

        $tickets = Ticket::where('client_id', $clientId)
            ->withCount('replies')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Portal/Tickets', [
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'product' => 'required|string',
        ]);

        // Automatically link to the authenticated client
        $data['client_id'] = Auth::guard('portal')->user()->client_id;
        $data['ticket_number'] = 'TKT-' . strtoupper(Str::random(6));

        Ticket::create($data);

        return back()->with('message', 'Ticket submitted successfully.');
    }
}
