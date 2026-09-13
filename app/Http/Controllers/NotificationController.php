<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->userNotifications()->paginate(20);
        $view = auth()->user()->is_member ? 'notifications' : 'guest-notifications';
        return $this->view($view, compact('notifications'));
    }

    // JSON feed for the bell dropdown
    public function unread()
    {
        $notifications = auth()->user()
            ->userNotifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($n) => [
                'id'        => $n->id,
                'message'   => $n->message,
                'read'      => (bool) $n->read_at,
                'time'      => $n->created_at->diffForHumans(),
            ]);

        $unreadCount = auth()->user()->userNotifications()->whereNull('read_at')->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function read($id)
    {
        auth()->user()->userNotifications()->where('id', $id)->update(['read_at' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back();
    }

    public function readAll()
    {
        auth()->user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back();
    }

    public function destroy($id)
    {
        auth()->user()->userNotifications()->where('id', $id)->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back();
    }
}
