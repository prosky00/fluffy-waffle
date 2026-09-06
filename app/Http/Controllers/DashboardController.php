<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('author')->latest()->take(10)->get();

        // Online = last_active_at within 5 minutes, sorted by rank level desc then name
        $onlineMembers = User::with(['rank', 'department'])
            ->where('last_active_at', '>=', now()->subMinutes(5))
            ->where('is_suspended', false)
            ->get()
            ->sortBy([
                fn($a, $b) => ($b->rank?->level ?? -1) <=> ($a->rank?->level ?? -1),
                fn($a, $b) => strcmp($a->in_game_name ?? $a->name, $b->in_game_name ?? $b->name),
            ])
            ->values();

        return $this->view('dashboard', compact('announcements', 'onlineMembers'));
    }
}
