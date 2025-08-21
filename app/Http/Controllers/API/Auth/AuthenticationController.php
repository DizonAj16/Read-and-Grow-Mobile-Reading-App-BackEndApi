<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Requests\Auth\AdminRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StudentRegisterRequest;
use App\Http\Requests\Auth\TeacherRegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponse;

class AuthenticationController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $auth)
    {
    }

    public function registerAdmin(AdminRegisterRequest $request)
    {
        $user = $this->auth->registerAdmin($request->validated());

        return $this->success([
            'user' => new UserResource($user),
        ], 'Admin registered', 201);
    }

    public function registerTeacher(TeacherRegisterRequest $request)
    {
        $user = $this->auth->registerTeacher($request->validated());

        return $this->success([
            'user' => new UserResource($user),
        ], 'Teacher registered', 201);
    }

    public function registerStudent(StudentRegisterRequest $request)
    {
        $user = $this->auth->registerStudent($request->validated());

        return $this->success([
            'user' => new UserResource($user),
        ], 'Student registered', 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        $user = $this->auth->findUserByLogin($data['login']);

        if (!$user || !$this->auth->verifyPassword($user, $data['password'])) {
            return $this->fail('Invalid credentials', 401);
        }

        $tokenInfo = $this->auth->issueToken($user);

        return $this->success([
            'token' => $tokenInfo['token'],
            'expires_in' => $tokenInfo['expires_in'],
            'user' => new UserResource($user),
        ], 'Login successful');
    }

    public function adminLogin(AdminLoginRequest $request)
    {
        $data = $request->validated();
        $user = $this->auth->findUserByLogin($data['login']);

        // Check if user exists and is admin
        if (!$user || $user->role !== User::ROLE_ADMIN || !$this->auth->verifyPassword($user, $data['password'])) {
            return $this->fail('Invalid admin credentials', 401);
        }

        // If security code is NOT provided yet → send step 2 signal
        if (!isset($data['admin_security_code']) || trim($data['admin_security_code']) === '') {
            return response()->json([
                'success' => false,
                'step' => 2,
                'message' => 'Please enter your admin security code',
            ], 401);
        }

        // If security code is provided but incorrect
        if (!$this->auth->validateAdminSecurityCode($user, $data['admin_security_code'])) {
            return $this->fail('Incorrect admin security code', 401);
        }

        // If everything is correct → issue token
        $tokenInfo = $this->auth->issueToken($user);

        return $this->success([
            'token' => $tokenInfo['token'],
            'expires_in' => $tokenInfo['expires_in'],
            'user' => new UserResource($user),
        ], 'Admin login successful');
    }


    public function logout()
    {
        request()->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logged out');
    }
}
