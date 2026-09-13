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
        $pendingApplications = FactionApplication::with('user')->where('status', 'PENDING')->latest()->get();
        $awaitingSlot         = FactionApplication::with('user')->where('status', 'APPROVED')->whereNotNull('proposed_slots')->latest()->get();
        $awaitingDecision     = FactionApplication::with(['user', 'scheduler'])->where('status', 'SCHEDULED')->latest()->get();
        $notifications        = Notification::with('user')->latest()->get();

        return $this->view('hr', compact('formFields', 'pendingApplications', 'awaitingSlot', 'awaitingDecision', 'notifications'));
    }
}
