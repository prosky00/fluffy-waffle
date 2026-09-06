<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'short_name', 'max_members', 'logo_url', 'rules', 'discord_role_id', 'discord_channel_id'];

    public function members()
    {
        return $this->belongsToMany(User::class, 'department_user')->withTimestamps();
    }

    public function ranks()
    {
        return $this->hasMany(DepartmentRank::class)->orderBy('level', 'desc');
    }

    public function officialConversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function syncConversation(): Conversation
    {
        $conv = Conversation::firstOrCreate(
            ['department_id' => $this->id],
            [
                'type'               => 'GROUP',
                'name'               => $this->name,
                'discord_role_id'    => $this->discord_role_id,
                'discord_channel_id' => $this->discord_channel_id,
            ]
        );

        // Ensure all current members are participants
        $memberIds = $this->members()->pluck('users.id');
        $conv->participants()->syncWithoutDetaching($memberIds);

        return $conv;
    }
}
