<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'student_name',
        'student_lrn',
        'student_grade',
        'student_section',
        'class_room_id',
        'profile_picture',
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class); // ✅ Each student belongs to one class
    }
}
