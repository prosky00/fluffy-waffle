<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCategory extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'sort_order'];

    protected static function booted(): void
    {
        static::saving(function (self $cat) {
            $cat->slug = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $cat->slug));
        });
    }
}
