<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavLink extends Model
{
    protected $fillable = ['label', 'url', 'is_external', 'sort_order'];

    protected $casts = ['is_external' => 'boolean'];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
