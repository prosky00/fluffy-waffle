<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;

class AllomanyController extends Controller
{
    public function index()
    {
        $weekAgo = Carbon::now()->subDays(7);

        $weeklyReportCounts = Report::where('created_at', '>=', $weekAgo)
            ->selectRaw('author_id, count(*) as c')
            ->groupBy('author_id')
            ->pluck('c', 'author_id');

        $members = User::where('is_suspended', false)
            ->with(['rank', 'departments'])
            ->orderBy('created_at')
            ->get()
            ->map(function (User $u) use ($weeklyReportCounts) {
                return [
                    'character_name' => $u->in_game_name ?? $u->name,
                    'rank'           => $u->rank?->name ?? '—',
                    'rank_color'     => $u->rank?->color ?? '#6b7280',
                    'departments'    => $u->departments->pluck('short_name')->implode(', '),
                    'joined_at'      => $u->created_at,
                    'weekly_reports' => $weeklyReportCounts[$u->id] ?? 0,
                    'last_rankup'    => $u->rank_up_date,
                ];
            });

        return $this->view('allomany', compact('members'));
    }
}
