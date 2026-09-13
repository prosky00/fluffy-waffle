<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['type', 'name', 'rank_id', 'discord_role_id', 'discord_channel_id', 'department_id'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')->withPivot(['starred', 'trashed_at']);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** Human-readable name. Pass the viewing user for a DIRECT conversation to get "the other participant"; omit for a neutral (e.g. admin overview) label. */
    public function displayName(?User $viewer = null): string
    {
        if ($this->type === 'RANK') {
            return $this->rank?->name ?? 'Rang';
        }
        if ($this->type === 'DIRECT') {
            if ($viewer) {
                $other = $this->participants->firstWhere('id', '!=', $viewer->id);
                return $other ? ($other->in_game_name ?? $other->name) : 'Ismeretlen';
            }
            $names = $this->participants->map(fn($p) => $p->in_game_name ?? $p->name);
            return $names->isNotEmpty() ? $names->implode(' ↔ ') : 'Ismeretlen';
        }
        return $this->name ?? 'Csoport';
    }

    public function typeLabel(): string
    {
        return ['DIRECT' => 'Közvetlen', 'GROUP' => 'Csoport', 'RANK' => 'Rang alapú'][$this->type] ?? $this->type;
    }
}
