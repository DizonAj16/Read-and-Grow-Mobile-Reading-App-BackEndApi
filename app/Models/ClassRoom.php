<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClassRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'class_name',
        'grade_level',
        'grade_level_id',   
        'section',
        'school_year',
        'classroom_code',
        'number_of_students',
        'active',
        'background_image',

    ];

    // Automatically generate unique classroom code on create
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($classroom) {
            if (empty($classroom->classroom_code)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (self::where('classroom_code', $code)->exists());

                $classroom->classroom_code = $code;
            }
        });
    }

    // Relationships

    public function students()
    {
        return $this->hasMany(Student::class, 'class_room_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'id');
    }


    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }


}
