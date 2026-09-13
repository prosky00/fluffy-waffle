<?php

namespace App\Http\Controllers;

use App\Models\FactionApplication;
use App\Models\Notification;
use App\Services\DiscordService;
use Illuminate\Http\Request;

class SchedulingController extends Controller
{
    public function confirmSlot(Request $request, $id)
    {
        $application = FactionApplication::where('status', 'APPROVED')->findOrFail($id);
        $data = $request->validate(['slot_index' => 'required|integer|min:0']);

        $slots = $application->proposed_slots ?? [];
        if (!isset($slots[$data['slot_index']])) {
            return back()->withErrors(['slot' => 'Érvénytelen időpont.']);
        }
        $slot = $slots[$data['slot_index']];

        $application->update([
            'status'               => 'SCHEDULED',
            'confirmed_date'       => $slot['date'],
            'confirmed_start_time' => $slot['start_time'],
            'confirmed_end_time'   => $slot['end_time'],
            'scheduled_by'         => auth()->id(),
            'scheduled_at'         => now(),
        ]);

        $message = "Interjú időpontod megerősítve: {$slot['date']} {$slot['start_time']}–{$slot['end_time']}.";
        $this->notify($application, $message);

        return back()->with('success', 'Időpont megerősítve.');
    }

    public function grantAccess($id)
    {
        $application = FactionApplication::where('status', 'SCHEDULED')->findOrFail($id);
        $application->user->update(['is_member' => true]);
        $application->update(['status' => 'MEMBER']);

        $this->notify($application, 'Gratulálunk! Az interjú után a jelentkezésed elfogadva, mostantól elérheted a belső felületet.');
        $this->logAudit('✅ Hozzáférés megadva (interjú után)', [
            'Végrehajtotta' => $this->actorName(),
            'Jelentkező'    => $application->user->in_game_name ?? $application->user->name,
        ]);

        return back()->with('success', 'Hozzáférés megadva.');
    }

    public function rejectAfterInterview($id)
    {
        $application = FactionApplication::where('status', 'SCHEDULED')->findOrFail($id);
        $application->update(['status' => 'REJECTED']);

        $this->notify($application, 'Az interjú után sajnos nem tudunk fiókot nyitni számodra. Új jelentkezést nyújthatsz be a Jelentkezéseim oldalon.');
        $this->logAudit('❌ Jelentkezés elutasítva (interjú után)', [
            'Végrehajtotta' => $this->actorName(),
            'Jelentkező'    => $application->user->in_game_name ?? $application->user->name,
        ]);

        return back()->with('success', 'Jelentkezés elutasítva.');
    }

    private function notify(FactionApplication $application, string $message): void
    {
        $applicant = $application->user;
        Notification::create(['user_id' => $applicant->id, 'message' => $message]);

        if ($applicant->discord_id) {
            app(DiscordService::class)->sendDm($applicant->discord_id, '', [
                'title'       => '📋 Jelentkezés',
                'description' => $message,
                'color'       => hexdec('F97316'),
                'timestamp'   => now()->toIso8601String(),
            ]);
        }
    }
}
