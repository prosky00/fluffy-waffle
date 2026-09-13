<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyWeeklyEntry extends Model
{
    protected $fillable = ['user_id', 'week_start', 'minutes'];

    protected function casts(): array
    {
        return ['week_start' => 'date'];
    }

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
