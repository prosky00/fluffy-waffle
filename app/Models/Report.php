<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'author_id', 'title', 'content', 'category', 'status', 'admin_comment',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function connectedUsers()
    {
        return $this->belongsToMany(User::class, 'report_connected_users');
    }
}
