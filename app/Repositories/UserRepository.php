<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function findByLogin(string $login): ?User
    {
        if ($user = User::where('username', $login)->first()) {
            return $user;
        }

        if ($teacher = DB::table('teachers')->where('teacher_email', $login)->first()) {
            return User::find($teacher->user_id);
        }

        if ($admin = DB::table('admins')->where('admin_email', $login)->first()) {
            return User::find($admin->user_id);
        }

        return null;
    }
}
