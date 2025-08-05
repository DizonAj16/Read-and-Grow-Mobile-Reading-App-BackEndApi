<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'title',
        'description',
        'max_attempts'
    ];

    // ✅ A Task belongs to a Grade
    public function grade()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    // ✅ A Task has many Student Progress Records
    public function progress()
    {
        return $this->hasMany(StudentTaskProgress::class);
    }
}
