<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Role constants
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';

    protected $fillable = [
        'username', 'password', 'role',

        'admin_email', 'admin_security_code',
        'teacher_name', 'teacher_email', 'teacher_position',
        'student_name', 'student_lrn', 'student_grade', 'student_section',
    ];

    protected $hidden = ['password'];

    // Helper methods for role checks
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher()
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent()
    {
        return $this->role === self::ROLE_STUDENT;
    }

}
