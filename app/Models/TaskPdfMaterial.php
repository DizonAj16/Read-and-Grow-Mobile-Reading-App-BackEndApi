<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskPdfMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_room_id',
        'teacher_id',
        'pdf_title',
        'pdf_file_path',
        'uploaded_at',
    ];

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
