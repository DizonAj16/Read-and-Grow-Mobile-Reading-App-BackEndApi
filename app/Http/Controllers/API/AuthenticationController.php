<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\ClassRoomController;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthenticationController extends Controller
{
    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_TEACHER = 'teacher';
    const ROLE_STUDENT = 'student';

    // Helper: Standard validation error response
    protected function validationErrorResponse($errors, $customMessages = [])
    {
        foreach ($customMessages as $field => $message) {
            if ($errors->has($field)) {
                return response()->json(['message' => $message], 422);
            }
        }
        return response()->json([
            'message' => 'Validation error',
            'errors' => $errors
        ], 422);
    }

    // Helper: Find user by login (username, teacher_email, admin_email)
    protected function findUserByLogin($login)
    {
        $user = User::where('username', $login)->first();
        if ($user)
            return $user;

        $teacher = \DB::table('teachers')->where('teacher_email', $login)->first();
        if ($teacher)
            return User::find($teacher->user_id);

        $admin = \DB::table('admins')->where('admin_email', $login)->first();
        if ($admin)
            return User::find($admin->user_id);

        return null;
    }

    public function registerAdmin(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'admin_email' => 'required|email|unique:admins,admin_email',
            'admin_password' => 'required|min:6|confirmed',
            'admin_security_code' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), [
                'username' => 'Username is already taken',
                'admin_email' => 'Admin email is already taken'
            ]);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => bcrypt($request->admin_password),
            'role' => 'admin'
        ]);

        \DB::table('admins')->insert([
            'user_id' => $user->id,
            'username' => $user->username,
            'admin_email' => $request->admin_email,
            'admin_security_code' => $request->admin_security_code,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Admin registered'], 201);
    }

    public function registerTeacher(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'teacher_username' => 'required|unique:users,username',
            'teacher_email' => 'required|email|unique:teachers,teacher_email',
            'teacher_password' => 'required|min:6|confirmed',
            'teacher_name' => 'required',
            'teacher_position' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), [
                'teacher_username' => 'Username is already taken',
                'teacher_email' => 'Teacher email is already taken'
            ]);
        }

        $user = User::create([
            'username' => $request->teacher_username,
            'password' => bcrypt($request->teacher_password),
            'role' => 'teacher'
        ]);

        \DB::table('teachers')->insert([
            'user_id' => $user->id,
            'username' => $user->username,
            'teacher_email' => $request->teacher_email,
            'teacher_name' => $request->teacher_name,
            'teacher_position' => $request->teacher_position,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Teacher registered'], 201);
    }

    public function registerStudent(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'student_username' => 'required|unique:users,username',
            'student_password' => 'required|min:6|confirmed',
            'student_name' => 'required',
            'student_lrn' => 'required|unique:students,student_lrn',
            'student_grade' => 'required',
            'student_section' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), [
                'student_username' => 'Username is already taken',
                'student_lrn' => 'Student LRN is already taken'
            ]);
        }

        $user = User::create([
            'username' => $request->student_username,
            'password' => bcrypt($request->student_password),
            'role' => 'student'
        ]);

        \DB::table('students')->insert([
            'user_id' => $user->id,
            'username' => $user->username,
            'student_name' => $request->student_name,
            'student_lrn' => $request->student_lrn,
            'student_grade' => $request->student_grade,
            'student_section' => $request->student_section,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Student registered'], 201);
    }

    public function login(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'login' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $this->findUserByLogin($request->login);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_in' => config('sanctum.expiration'),
            'role' => $user->role
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }



    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $this->findUserByLogin($request->login);

        if (!$user || $user->role !== self::ROLE_ADMIN) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $admin = \DB::table('admins')->where('user_id', $user->id)->first();

        if (!$request->filled('admin_security_code')) {
            return response()->json([
                'message' => 'Admin security code required'
            ], 401);
        }

        if (!$admin || $admin->admin_security_code !== $request->admin_security_code) {
            return response()->json([
                'message' => 'Incorrect admin security code'
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_in' => config('sanctum.expiration'),
            'role' => $user->role
        ]);
    }

}
