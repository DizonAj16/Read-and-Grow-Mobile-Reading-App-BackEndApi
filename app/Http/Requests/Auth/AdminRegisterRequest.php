<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AdminRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username'            => 'required|string|unique:users,username',
            'admin_email'         => 'required|email|unique:admins,admin_email',
            'admin_password'      => 'required|string|min:6|confirmed',
            'admin_security_code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'    => 'Username is already taken',
            'admin_email.unique' => 'Admin email is already taken',
        ];
    }
}
