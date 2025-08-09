<?php
// app/Http/Resources/LoginResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'auth' => [
                'token' => $this->token,
                'role' => $this->role,
                'expires_in' => config('sanctum.expiration')
            ],
            'user' => [
                'id' => $this->id,
                'username' => $this->username,
            ],
            'profile' => $this->when($this->details, function () {
                // Common fields for all roles
                $profile = [
                    'id' => $this->details->id, // Include profile ID
                    'avatar' => $this->details->profile_picture
                        ? url('storage/profile_images/' . $this->details->profile_picture)
                        : null
                ];

                // Role-specific fields
                if ($this->role === 'student') {
                    $profile += [
                        'name' => $this->details->student_name ?? null,
                        'lrn' => $this->details->student_lrn ?? null,
                        'grade' => $this->details->student_grade ?? null,
                        'section' => $this->details->student_section ?? null,
                        'class_room_id' => $this->details->class_room_id ?? null // Include classroom ID if needed
                    ];
                } elseif ($this->role === 'teacher') {
                    $profile += [
                        'name' => $this->details->teacher_name ?? null,
                        'email' => $this->details->teacher_email ?? null,
                        'position' => $this->details->teacher_position ?? null
                    ];
                }

                return $profile;
            }),
            'classroom' => $this->when($this->role === 'student' && $this->student_class, function () {
                return [
                    'id' => $this->student_class[0]->id ?? null,
                    'name' => $this->student_class[0]->class_name ?? null,
                    'section' => $this->student_class[0]->section ?? null,
                    'grade_level' => $this->student_class[0]->grade_level ?? null,
                    'school_year' => $this->student_class[0]->school_year ?? null,
                    'classroom_code' => $this->student_class[0]->classroom_code ?? 'N/A',
                    'background_image' => isset($this->student_class[0]->background_image_url)
                        ? $this->student_class[0]->background_image_url
                        : null,
                    'teacher' => [
                        'id' => $this->student_class[0]->teacher_id ?? null,
                        'name' => $this->student_class[0]->teacher_name ?? null,
                        'email' => $this->student_class[0]->teacher_email ?? null,
                        'position' => $this->student_class[0]->teacher_position ?? null,
                        'avatar' => isset($this->student_class[0]->teacher_avatar_url)
                            ? $this->student_class[0]->teacher_avatar_url
                            : null,
                    ]
                ];
            })
        ];
    }
}