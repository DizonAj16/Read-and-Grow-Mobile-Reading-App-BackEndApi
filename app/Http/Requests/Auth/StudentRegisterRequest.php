<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StudentRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'student_username' => 'required|string|unique:users,username',
            'student_password' => 'required|string|min:6|confirmed',
            'student_name'     => 'required|string',
            'student_lrn'      => 'required|string|unique:students,student_lrn',
            'student_grade'    => 'required|string',
            'student_section'  => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'student_username.unique' => 'Username is already taken',
            'student_lrn.unique'      => 'Student LRN is already taken',
        ];
    }
}
