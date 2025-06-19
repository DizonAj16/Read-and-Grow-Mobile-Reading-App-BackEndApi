<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;

class UserController extends Controller
{
    /**
     * Get admin user and details by user ID.
     */
    public function getAdmin($id)
    {
        $user = $this->findUserByRole($id, User::ROLE_ADMIN);
        if (!$user) {
            return response()->json(['message' => 'Admin not found'], 404);
        }
        $admin = Admin::where('user_id', $user->id)->first();
        return response()->json([
            'user' => $user,
            'admin' => $admin
        ]);
    }

    /**
     * Get teacher user and details by user ID.
     */
    public function getTeacher($id)
    {
        $user = $this->findUserByRole($id, User::ROLE_TEACHER);
        if (!$user) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }
        $teacher = Teacher::where('user_id', $user->id)->first();
        return response()->json([
            'user' => $user,
            'teacher' => $teacher
        ]);
    }

    /**
     * Get student user and details by user ID.
     */
    public function getStudent($id)
    {
        $user = $this->findUserByRole($id, User::ROLE_STUDENT);
        if (!$user) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        $student = Student::where('user_id', $user->id)->first();
        return response()->json([
            'user' => $user,
            'student' => $student
        ]);
    }

    /**
     * Get all students (teacher-only access).
     * Requires authentication and teacher role.
     */
    public function getAllStudents(Request $request)
    {
        $user = $request->user();
        if (!$user || (!$user->isTeacher() && !$user->isAdmin())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $students = Student::with('user:id,username')->get()->map(function ($student) {
            return [
                'student_id' => $student->id,
                'user_id' => $student->user_id, // <-- Add this line!
                'student_name' => $student->student_name,
                'student_lrn' => $student->student_lrn,
                'student_grade' => $student->student_grade,
                'student_section' => $student->student_section,
                'username' => $student->user ? $student->user->username : null,
            ];
        });
        return response()->json(['students' => $students]);
    }

    public function getAllTeachers(Request $request)
    {
        $user = $request->user();
        if (!$user || (!$user->isTeacher() && !$user->isAdmin())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $teachers = Teacher::with('user:id,username')->get()->map(function ($teacher) {
            return [
                'teacher_id' => $teacher->id,
                'user_id' => $teacher->user_id, // <-- Add this line!
                'teacher_name' => $teacher->teacher_name,
                'teacher_email' => $teacher->teacher_email,
                'teacher_position' => $teacher->teacher_position,
                'username' => $teacher->user ? $teacher->user->username : null,
            ];
        });
        return response()->json(['teachers' => $teachers]);
    }

    // --- Private helper methods ---

    /**
     * Find a user by ID and role.
     */
    private function findUserByRole($id, $role)
    {
        return User::where('id', $id)->where('role', $role)->first();
    }
    public function deleteUser(Request $request, $id)
    {
        $user = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Teachers can only delete students
        if ($user->isTeacher()) {
            if ($targetUser->role !== User::ROLE_STUDENT) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        // Admins can delete students and teachers
        if ($user->isAdmin()) {
            if (!in_array($targetUser->role, [User::ROLE_STUDENT, User::ROLE_TEACHER])) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        // Only teachers and admins can delete, so if not either, forbid
        if (!$user->isTeacher() && !$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $targetUser->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}