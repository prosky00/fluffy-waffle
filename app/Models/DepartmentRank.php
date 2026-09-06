<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentRank extends Model
{
    protected $fillable = ['department_id', 'name', 'level'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
