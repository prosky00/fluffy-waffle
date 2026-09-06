<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $fillable = ['name', 'color', 'level', 'is_admin', 'discord_role_id'];

    protected function casts(): array
    {
        return ['is_admin' => 'boolean'];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
