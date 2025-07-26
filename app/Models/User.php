<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Role constants for easy access and comparison.
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';

    /**
     * Fields that can be mass assigned.
     */
    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    /**
     * Fields that should be hidden in arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Automatically cast attributes.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ────────────────────────────────────────────────
    //                 Role Helper Methods
    // ────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    // ────────────────────────────────────────────────
    //                Relationships
    // ────────────────────────────────────────────────

    /**
     * Get the admin profile related to this user.
     */
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * Get the teacher profile related to this user.
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Get the student profile related to this user.
     */
    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'id');
    }

}
