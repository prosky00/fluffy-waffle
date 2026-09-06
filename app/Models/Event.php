<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'description', 'location', 'event_time', 'duration_minutes', 'max_persons', 'created_by'];

    protected function casts(): array
    {
        return [
            'event_time'       => 'datetime',
            'duration_minutes' => 'integer',
            'max_persons'      => 'integer',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rsvps()
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function goingRsvps()
    {
        return $this->rsvps()->where('status', 'GOING');
    }

    public function getIsFull(): bool
    {
        if ($this->max_persons === 0) return false;
        return $this->goingRsvps()->count() >= $this->max_persons;
    }
}
