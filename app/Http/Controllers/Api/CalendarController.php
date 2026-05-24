<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function getEvents(Request $request)
    {
        $events = [];

        // 1. Fetch Meetings
        $meetings = Meeting::with('attendees.user:id,name,avatar')
            ->where('start_time', '>=', now()->subMonths(1))
            ->where('start_time', '<=', now()->addMonths(3))
            ->get();

        foreach ($meetings as $m) {
            $events[] = [
                'id' => $m->id,
                'title' => $m->title,
                'start' => $m->start_time,
                'end' => $m->end_time,
                'type' => 'meeting',
                'color' => '#2563EB', // Blue
                'data' => $m
            ];
        }

        // 2. Fetch Tasks with due dates
        $tasks = Task::whereNotNull('due_date')
            ->whereIn('status', ['todo', 'in_progress', 'review'])
            ->get();

        foreach ($tasks as $t) {
            $events[] = [
                'id' => $t->id,
                'title' => 'Task: ' . $t->title,
                'start' => $t->due_date,
                'end' => $t->due_date,
                'type' => 'task',
                'color' => '#0F172A', // Dev Navy
                'data' => $t
            ];
        }

        // 3. Fetch Invoices due dates
        $invoices = Invoice::whereNotNull('due_date')
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get();

        foreach ($invoices as $i) {
            $events[] = [
                'id' => $i->id,
                'title' => 'Invoice Due: ' . $i->invoice_number,
                'start' => $i->due_date,
                'end' => $i->due_date,
                'type' => 'invoice',
                'color' => '#CA8A04', // Finance Gold
                'data' => $i
            ];
        }

        // 4. Fetch Approved Leaves
        $leaves = LeaveRequest::with('user:id,name')
            ->where('status', 'approved')
            ->get();

        foreach ($leaves as $l) {
            $events[] = [
                'id' => $l->id,
                'title' => 'Leave: ' . ($l->user->name ?? 'User'),
                'start' => $l->start_date,
                'end' => $l->end_date,
                'type' => 'leave',
                'color' => '#0891B2', // HR Cyan
                'data' => $l
            ];
        }

        return response()->json(['events' => $events]);
    }

    public function storeMeeting(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:internal,external,video,audio,in_person',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'agenda' => 'nullable|string',
        ]);

        $data['created_by'] = Auth::id();
        
        if ($data['type'] === 'video') {
            $data['video_room_id'] = 'ROOM-' . strtoupper(substr(uniqid(), -6));
            $data['join_url'] = '/app/meetings/room/' . $data['video_room_id'];
        }

        $meeting = Meeting::create($data);

        return response()->json(['message' => 'Meeting scheduled', 'meeting' => $meeting]);
    }
}
