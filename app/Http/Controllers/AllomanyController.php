<?php

namespace App\Http\Controllers;

use App\Models\DutyWeeklyEntry;
use App\Models\FactionSetting;
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

        $weekStart   = DutyWeeklyEntry::currentWeekStart();
        $dutyMinutes = DutyWeeklyEntry::where('week_start', $weekStart)->pluck('minutes', 'user_id');

        $settings         = FactionSetting::singleton();
        $minutesThreshold = $settings->duty_minutes_threshold;
        $requiredReports  = $settings->required_reports_count;

        $members = User::where('is_suspended', false)
            ->with(['rank', 'departments'])
            ->orderBy('created_at')
            ->get()
            ->map(function (User $u) use ($weeklyReportCounts, $dutyMinutes, $minutesThreshold, $requiredReports) {
                $weeklyReports = $weeklyReportCounts[$u->id] ?? 0;
                $minutes       = $dutyMinutes[$u->id] ?? 0;
                $meetsMinutes  = $minutesThreshold === null || $minutes >= $minutesThreshold;
                $meetsReports  = $requiredReports === null || $weeklyReports >= $requiredReports;

                return [
                    'character_name' => $u->in_game_name ?? $u->name,
                    'rank'           => $u->rank?->name ?? '—',
                    'rank_color'     => $u->rank?->color ?? '#6b7280',
                    'departments'    => $u->departments->pluck('short_name')->implode(', '),
                    'joined_at'      => $u->created_at,
                    'weekly_reports' => $weeklyReports,
                    'last_rankup'    => $u->rank_up_date,
                    'duty_minutes'   => $minutes,
                    'duty_ok'        => $meetsMinutes,
                    'pay_eligible'   => $meetsMinutes && $meetsReports,
                ];
            });

        return $this->view('allomany', compact('members', 'minutesThreshold', 'requiredReports', 'weekStart'));
    }
}
