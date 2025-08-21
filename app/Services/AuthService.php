<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function findUserByLogin(string $login): ?User
    {
        return $this->users->findByLogin($login);
    }

    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    public function issueToken(User $user): array
    {
        return [
            'token'      => $user->createToken('api-token')->plainTextToken,
            'expires_in' => config('sanctum.expiration'),
        ];
    }

    public function registerAdmin(array $data): User
    {
        $user = User::create([
            'username' => $data['username'],
            'password' => bcrypt($data['admin_password']),
            'role'     => User::ROLE_ADMIN,
        ]);

        DB::table('admins')->insert([
            'user_id'             => $user->id,
            'username'            => $user->username,
            'admin_email'         => $data['admin_email'],
            'admin_security_code' => $data['admin_security_code'],
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return $user;
    }

    public function registerTeacher(array $data): User
    {
        $user = User::create([
            'username' => $data['teacher_username'],
            'password' => bcrypt($data['teacher_password']),
            'role'     => User::ROLE_TEACHER,
        ]);

        DB::table('teachers')->insert([
            'user_id'          => $user->id,
            'username'         => $user->username,
            'teacher_email'    => $data['teacher_email'],
            'teacher_name'     => $data['teacher_name'],
            'teacher_position' => $data['teacher_position'],
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return $user;
    }

    public function registerStudent(array $data): User
    {
        $user = User::create([
            'username' => $data['student_username'],
            'password' => bcrypt($data['student_password']),
            'role'     => User::ROLE_STUDENT,
        ]);

        DB::table('students')->insert([
            'user_id'         => $user->id,
            'username'        => $user->username,
            'student_name'    => $data['student_name'],
            'student_lrn'     => $data['student_lrn'],
            'student_grade'   => $data['student_grade'],
            'student_section' => $data['student_section'],
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return $user;
    }

    public function validateAdminSecurityCode(User $adminUser, string $code): bool
    {
        $admin = DB::table('admins')->where('user_id', $adminUser->id)->first();
        return $admin && hash_equals((string) $admin->admin_security_code, (string) $code);
    }
}
