<?php

namespace App\Http\Controllers;

use App\Models\ApplicationFormField;
use App\Models\FactionApplication;
use App\Models\Notification;

class HrController extends Controller
{
    public function index()
    {
        $formFields          = ApplicationFormField::ordered()->get();
        $pendingApplications  = FactionApplication::with('user')->where('status', 'PENDING')->latest()->get();
        $awaitingSlot         = FactionApplication::with('user')->where('status', 'APPROVED')->whereNotNull('proposed_slots')->latest()->get();
        $awaitingDecision     = FactionApplication::with(['user', 'scheduler'])->where('status', 'SCHEDULED')->latest()->get();
        // Rejected, or approved-but-not-yet-proposed-a-slot, or fully granted — otherwise
        // these vanish from every HR view the moment they leave PENDING, with no way to
        // look back at what was already decided.
        $reviewedApplications = FactionApplication::with(['user', 'reviewer'])
            ->where(function ($q) {
                $q->whereIn('status', ['REJECTED', 'MEMBER'])
                  ->orWhere(fn($q2) => $q2->where('status', 'APPROVED')->whereNull('proposed_slots'));
            })
            ->latest()->get();
        $notifications        = Notification::with('user')->latest()->get();

        return $this->view('hr', compact('formFields', 'pendingApplications', 'awaitingSlot', 'awaitingDecision', 'reviewedApplications', 'notifications'));
    }
}
