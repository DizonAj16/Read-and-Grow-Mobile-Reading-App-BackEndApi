<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;

class UserController extends Controller
{
    /**
     * Get user details by role.
     */
    public function getUserByRole($id, $role)
    {
        $user = User::where('id', $id)->where('role', $role)->first();

        if (!$user) {
            return response()->json(['message' => ucfirst($role) . ' not found'], 404);
        }

        // Load the role-specific model dynamically
        $modelMap = [
            User::ROLE_ADMIN => Admin::class,
            User::ROLE_TEACHER => Teacher::class,
            User::ROLE_STUDENT => Student::class,
        ];

        if (!isset($modelMap[$role])) {
            return response()->json(['message' => 'Unsupported role'], 422);
        }

        $profile = $modelMap[$role]::where('user_id', $user->id)->first();

        return response()->json([
            'user' => $user,
            'profile' => $profile
        ]);
    }

    /**
     * Get all students.
     */
    public function getAllStudents()
    {
        $students = Student::with('user:id,username')->get()->map(function ($student) {
            return [
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'student_name' => $student->student_name,
                'student_lrn' => $student->student_lrn,
                'student_grade' => $student->student_grade,
                'student_section' => $student->student_section,
                'username' => optional($student->user)->username,
                'profile_picture' => $student->profile_picture,
            ];
        });

        return response()->json(['students' => $students]);
    }

    /**
     * Get all teachers.
     */
    public function getAllTeachers()
    {
        $teachers = Teacher::with('user:id,username')->get()->map(function ($teacher) {
            return [
                'teacher_id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'teacher_name' => $teacher->teacher_name,
                'teacher_email' => $teacher->teacher_email,
                'teacher_position' => $teacher->teacher_position,
                'username' => optional($teacher->user)->username,
            ];
        });

        return response()->json(['teachers' => $teachers]);
    }

    /**
     * Delete a user.
     */
    public function deleteUser($id)
    {
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $targetUser->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Update a user.
     */
    public function updateUser(Request $request, $id)
    {
        $authUser = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Authorization
        if ($authUser->isTeacher() && $targetUser->role !== User::ROLE_STUDENT) {
            return response()->json(['message' => 'Forbidden: Teachers can only edit students'], 403);
        }

        if ($authUser->isAdmin() && !in_array($targetUser->role, [User::ROLE_STUDENT, User::ROLE_TEACHER])) {
            return response()->json(['message' => 'Forbidden: Cannot edit this type of user'], 403);
        }

        if (!($authUser->isAdmin() || $authUser->isTeacher())) {
            return response()->json(['message' => 'Forbidden: Only admins and teachers can edit'], 403);
        }

        // Update user table
        $targetUser->username = $request->input('username', $targetUser->username);
        $targetUser->save();

        // Role-specific updates
        if ($targetUser->role === User::ROLE_TEACHER) {
            $teacher = Teacher::where('user_id', $targetUser->id)->first();
            if ($teacher) {
                $teacher->update($request->only(['teacher_name', 'teacher_email', 'teacher_position', 'username']));
            }
        }

        if ($targetUser->role === User::ROLE_STUDENT) {
            $student = Student::where('user_id', $targetUser->id)->first();
            if ($student) {
                $student->update($request->only(['student_name', 'student_lrn', 'student_grade', 'student_section', 'username']));
            }
        }

        return response()->json(['message' => 'User updated successfully']);
    }

    /**
     * Upload profile picture for teacher/student.
     */
    public function uploadProfilePicture(Request $request, $role)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $modelMap = [
            User::ROLE_TEACHER => Teacher::class,
            User::ROLE_STUDENT => Student::class,
        ];

        if (!isset($modelMap[$role])) {
            return response()->json(['message' => 'Unsupported role'], 422);
        }

        $record = $modelMap[$role]::where('user_id', $request->user_id)->firstOrFail();

        // Delete old picture if exists
        if ($record->profile_picture) {
            Storage::disk('public')->delete(str_replace('storage/', '', $record->profile_picture));
        }

        // Save new picture
        $path = $request->file('profile_picture')->store('profile_images', 'public');

        // Save full relative path in DB
        $record->profile_picture = 'storage/profile_images/' . basename($path);
        $record->save();

        return response()->json([
            'message' => ucfirst($role) . ' profile updated',
            'profile_picture' => asset($record->profile_picture),
        ]);
    }


    /**
     * Get all student profile pictures.
     */
    public function getAllStudentProfilePictures()
    {
        $students = Student::with('user:id,username')->get()->map(function ($student) {
            return [
                'student_id' => $student->id,
                'username' => optional($student->user)->username,
                'profile_picture' => $student->profile_picture
                    ? asset('storage/profile_images/' . $student->profile_picture)
                    : asset('storage/profile_images/default.png'),
            ];
        });

        return response()->json(['students' => $students]);
    }

    /**
     * Get authenticated user's profile (role-aware).
     */
    public function getAuthProfile(Request $request)
    {
        $user = $request->user();
        return $this->getUserByRole($user->id, $user->role);
    }

    /**
     * Get all users (admin only).
     */
    public function getUsers(Request $request)
    {
        if ($request->user()->role !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(User::all());
    }
}
