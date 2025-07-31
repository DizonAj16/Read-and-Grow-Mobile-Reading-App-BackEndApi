<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTaskProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'task_id',
        'attempts_left',
        'score',
        'max_score',
        'activity_details',
        'correct_answers',
        'wrong_answers',
        'completed',
        'audio_submitted', // ✅ NEW
    ];


    // ✅ A Progress belongs to a Task
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // ✅ A Progress belongs to a Student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
