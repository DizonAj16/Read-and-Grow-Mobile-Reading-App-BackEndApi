<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    use HasFactory;

    // 👇 Explicitly define the new table name
    protected $table = 'grade_level';

    protected $fillable = ['name', 'level'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }
}
