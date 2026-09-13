<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationFormField extends Model
{
    protected $fillable = ['label', 'type', 'options', 'is_required', 'sort_order'];

    protected $casts = ['options' => 'array', 'is_required' => 'boolean'];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
