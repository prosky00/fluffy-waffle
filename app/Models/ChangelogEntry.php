<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangelogEntry extends Model
{
    protected $fillable = ['user_id', 'title', 'content'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
