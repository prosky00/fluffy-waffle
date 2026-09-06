<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
use Illuminate\Support\Carbon;

class AllomanyController extends Controller
{
    public function index()
    {
        $weekAgo = Carbon::now()->subDays(7);

        $members = User::where('is_suspended', false)
            ->with('rank')
            ->orderBy('created_at')
            ->get()
            ->map(function (User $u) use ($weekAgo) {
                return [
                    'character_name' => $u->in_game_name ?? $u->name,
                    'rank'           => $u->rank?->name ?? '—',
                    'rank_color'     => $u->rank?->color ?? '#6b7280',
                    'joined_at'      => $u->created_at,
                    'weekly_reports' => Report::where('user_id', $u->id)
                        ->where('created_at', '>=', $weekAgo)
                        ->count(),
                    'last_rankup'    => $u->rank_up_date,
                ];
            });

        return $this->view('allomany', compact('members'));
    }
}
