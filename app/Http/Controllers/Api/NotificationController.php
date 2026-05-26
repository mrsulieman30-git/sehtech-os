<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Notifications\SystemNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'unread' => $user->unreadNotifications,
            'all' => $user->notifications()->take(50)->get()
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }
        return response()->json(['success' => true]);
    }

    // Dummy endpoint for testing
    public function testNotification(Request $request)
    {
        $user = $request->user();
        $user->notify(new SystemNotification(
            'Test Notification',
            'This is a comprehensive test of the new notification system.',
            'bell',
            null,
            'info'
        ));
        
        return response()->json(['success' => true]);
    }
}
