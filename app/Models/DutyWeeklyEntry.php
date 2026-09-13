<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyWeeklyEntry extends Model
{
    protected $fillable = ['user_id', 'week_start', 'minutes'];

    // No 'date' cast on week_start: every write and read in this app compares
    // it as a plain 'Y-m-d' string (see currentWeekStart()) — casting it to
    // Carbon made Eloquent persist "2026-09-07 00:00:00" on save, which then
    // never matched the plain-string WHERE clause used to look it back up.

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Monday of the current week — the boundary duty minutes reset on. */
    public static function currentWeekStart(): string
    {
        return now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
    }
}
