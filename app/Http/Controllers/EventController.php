<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'location'         => 'nullable|string|max:255',
            'event_time'       => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:10080',
            'max_persons'      => 'nullable|integer|min:0',
        ]);

        Event::create(array_merge($data, [
            'created_by'       => auth()->id(),
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'max_persons'      => $data['max_persons'] ?? 0,
        ]));

        return redirect()->route('esemenyek')->with('success', 'Esemény létrehozva.');
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $data  = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'location'         => 'nullable|string|max:255',
            'event_time'       => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:10080',
            'max_persons'      => 'nullable|integer|min:0',
        ]);

        $event->update($data);
        return redirect()->route('esemenyek')->with('success', 'Esemény frissítve.');
    }

    public function destroy($id)
    {
        Event::findOrFail($id)->delete();
        return redirect()->route('esemenyek')->with('success', 'Esemény törölve.');
    }

    public function rsvp(Request $request, $id)
    {
        $data  = $request->validate(['status' => 'required|in:GOING,NOT_GOING,MAYBE']);
        $user  = auth()->user();
        $event = Event::findOrFail($id);

        if ($data['status'] === 'GOING' && $event->max_persons > 0) {
            $goingCount = EventRsvp::where('event_id', $id)->where('status', 'GOING')
                ->where('user_id', '!=', $user->id)->count();
            if ($goingCount >= $event->max_persons) {
                return back()->withErrors(['rsvp' => 'Az esemény betelt, nem tudsz jelentkezni.']);
            }
        }

        EventRsvp::updateOrCreate(
            ['event_id' => $id, 'user_id' => $user->id],
            ['status' => $data['status']]
        );

        return back()->with('success', 'RSVP frissítve.');
    }
}
