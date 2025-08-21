<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TeacherRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'teacher_username'  => 'required|string|unique:users,username',
            'teacher_email'     => 'required|email|unique:teachers,teacher_email',
            'teacher_password'  => 'required|string|min:6|confirmed',
            'teacher_name'      => 'required|string',
            'teacher_position'  => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_username.unique' => 'Username is already taken',
            'teacher_email.unique'    => 'Teacher email is already taken',
        ];
    }
}
