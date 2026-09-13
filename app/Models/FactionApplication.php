<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactionApplication extends Model
{
    protected $fillable = [
        'user_id', 'answers', 'status', 'review_note', 'fields_needing_changes',
        'proposed_slots', 'confirmed_date', 'confirmed_start_time', 'confirmed_end_time',
        'reviewed_by', 'reviewed_at', 'scheduled_by', 'scheduled_at',
    ];

    protected $casts = [
        'answers'                => 'array',
        'fields_needing_changes' => 'array',
        'proposed_slots'         => 'array',
        'reviewed_at'            => 'datetime',
        'scheduled_at'           => 'datetime',
        'confirmed_date'         => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
