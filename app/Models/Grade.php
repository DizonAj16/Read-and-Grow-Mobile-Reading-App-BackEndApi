<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'level'];

    // ✅ A Grade has many Tasks
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // ✅ A Grade can have many Classes (if you use class_rooms)
    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }
}
