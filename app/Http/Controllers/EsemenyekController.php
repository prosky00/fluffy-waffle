<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\FactionSetting;
use Illuminate\Http\Request;

class EsemenyekController extends Controller
{
    public function index()
    {
        $settings = FactionSetting::singleton();
        $user     = auth()->user();
        $events   = Event::with(['creator', 'rsvps.user'])
            ->orderBy('event_time', 'asc')
            ->get()
            ->map(function (Event $event) use ($user) {
                $event->user_rsvp   = $event->rsvps->firstWhere('user_id', $user->id);
                $event->going_count = $event->rsvps->where('status', 'GOING')->count();
                $event->maybe_count = $event->rsvps->where('status', 'MAYBE')->count();
                $event->going_users = $event->rsvps->where('status', 'GOING')->pluck('user');
                return $event;
            });

        return $this->view('esemenyek', compact('settings', 'events'));
    }

    public function update(Request $request)
    {
        $data = $request->validate(['events_content' => 'nullable|string']);
        FactionSetting::singleton()->update(['events_content' => $data['events_content'] ?? null]);
        return redirect()->route('esemenyek')->with('success', 'Leírás frissítve.');
    }
}
