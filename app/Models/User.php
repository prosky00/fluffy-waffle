<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'discord_id', 'name', 'email', 'avatar',
        'username', 'password', 'is_suspended',
        'in_game_name', 'rank_id', 'department_id', 'department_rank_id',
        'is_department_leader', 'is_department_deputy',
        'rank_up_date', 'is_admin', 'is_supervisor', 'is_hr', 'is_member',
        'last_active_at', 'notification_preference', 'duty_minutes',
    ];

    protected $hidden = ['remember_token', 'password'];

    protected function casts(): array
    {
        return [
            'is_department_leader' => 'boolean',
            'is_department_deputy' => 'boolean',
            'is_admin'             => 'boolean',
            'is_supervisor'        => 'boolean',
            'is_hr'                => 'boolean',
            'is_member'            => 'boolean',
            'is_suspended'         => 'boolean',
            'rank_up_date'         => 'datetime',
            'last_active_at'       => 'datetime',
            'password'             => 'hashed',
        ];
    }

    public function isOnline(): bool
    {
        return $this->last_active_at && $this->last_active_at->gt(now()->subMinutes(5));
    }

    public function isHr(): bool
    {
        if ($this->is_admin || $this->is_hr) return true;

        $hrDepartmentId = FactionSetting::singleton()->hr_department_id;
        return $hrDepartmentId && $this->departments->contains('id', $hrDepartmentId);
    }

    public function wantsNotification(string $type = 'general'): bool
    {
        if ($this->notification_preference === 'MESSAGES_ONLY') {
            return $type === 'message';
        }
        return true;
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function departmentRank()
    {
        return $this->belongsTo(DepartmentRank::class);
    }

    public function userNotifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'author_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_user')->withTimestamps();
    }
}
