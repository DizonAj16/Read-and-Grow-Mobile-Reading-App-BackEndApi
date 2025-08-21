<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Storage;
use Illuminate\Http\Request;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\GradeLevel;


class ClassRoomController extends Controller
{
    /**
     * Store a new classroom (teacher-only).
     */

    public function store(Request $request)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'grade_level' => 'required|string|max:50',
            'section' => 'nullable|string|max:50',
            'school_year' => 'nullable|string|max:20',
        ]);

        // 🔍 Lookup grade_level_id based on the grade_level value (e.g., '1', '2', etc.)
        $gradeLevel = GradeLevel::where('level', $validated['grade_level'])->first();

        if (!$gradeLevel) {
            return response()->json(['message' => 'Invalid grade level'], 422);
        }

        $class = ClassRoom::create([
            'teacher_id' => $teacher->id,
            'class_name' => $validated['class_name'],
            'grade_level' => $validated['grade_level'],
            'grade_level_id' => $gradeLevel->id, // ✅ assign correct grade_level_id
            'section' => $validated['section'] ?? null,
            'school_year' => $validated['school_year'] ?? null,
        ]);

        return response()->json([
            'message' => 'Class created successfully',
            'class' => $class
        ], 201);
    }


    /**
     * Get all classes of the authenticated teacher.
     */
    public function index(Request $request)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $classes = ClassRoom::with('teacher')
            ->where('teacher_id', $teacher->id)
            ->get()
            ->map(function ($class) {
                return [
                    'id' => $class->id,
                    'class_name' => $class->class_name,
                    'grade_level' => $class->grade_level,
                    'grade_level_id' => $class->grade_level_id,
                    'section' => $class->section,
                    'school_year' => $class->school_year,
                    'student_count' => $class->students()->count(),
                    'teacher_name' => $class->teacher->teacher_name ?? 'Unknown',
                    'classroom_code' => $class->classroom_code,
                    // Optional: include background image URL if needed
                    'background_image' => $class->background_image ? asset("storage/class_backgrounds/{$class->background_image}") : null,
                ];
            });

        return response()->json($classes);
    }

    /**
     * Show a specific classroom with students and teacher details.
     */
    public function show($id)
    {
        $classroom = ClassRoom::with(['students.user', 'teacher'])->find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        return response()->json([
            'id' => $classroom->id,
            'class_name' => $classroom->class_name,
            'grade_level' => $classroom->grade_level,
            'grade_level_id' => $classroom->grade_level_id,
            'section' => $classroom->section,
            'school_year' => $classroom->school_year,
            'student_count' => $classroom->students->count(),
            'teacher_name' => optional($classroom->teacher)->teacher_name ?? 'Unknown',
            'classroom_code' => $classroom->classroom_code,
            // Optional: include background image URL if needed
            'background_image' => $classroom->background_image ? asset("storage/class_backgrounds/{$classroom->background_image}") : null,
            // Include students with their details
            'students' => $classroom->students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_grade' => $student->student_grade,
                    'student_section' => $student->student_section,
                    'student_lrn' => $student->student_lrn,
                    'username' => optional($student->user)->username,
                    'profile_picture' => $student->profile_picture,
                    'class_room_id' => $student->class_room_id,
                ];
            }),
        ]);
    }

    /**
     * Delete a class owned by the authenticated teacher.
     */
    public function deleteClass(Request $request, $id)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $classroom = ClassRoom::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$classroom) {
            return response()->json(['message' => 'Class not found or you do not own this class'], 404);
        }

        // Optional: unassign students before deleting
        Student::where('class_room_id', $classroom->id)->update(['class_room_id' => null]);

        $classroom->delete();

        return response()->json(['message' => 'Class deleted successfully']);
    }

    /**
     * Update an existing class (only by its owner teacher).
     */
    public function updateClass(Request $request, $id)
    {


        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $classroom = ClassRoom::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$classroom) {
            return response()->json(['message' => 'Class not found or unauthorized'], 404);
        }

        $validated = $request->validate([
            'class_name' => 'sometimes|required|string|max:255',
            'grade_level' => 'sometimes|required|string|max:50',
            'section' => 'nullable|string|max:50',
            'school_year' => 'nullable|string|max:20',
        ]);

        $classroom->update($validated);

        return response()->json([
            'message' => 'Class updated successfully',
            'class' => $classroom
        ]);
    }

    /**
     * Assign a student to a class (teacher-only).
     */
    public function assignStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_room_id' => 'required|exists:class_rooms,id',
        ]);

        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $classroom = ClassRoom::where('id', $request->class_room_id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$classroom) {
            return response()->json(['message' => 'You do not own this class'], 403);
        }

        $student = Student::findOrFail($request->student_id);

        // 🔍 Check grade level match
        if ($student->student_grade !== $classroom->grade_level) {
            return response()->json([
                'message' => "Student grade ({$student->student_grade}) does not match class grade level ({$classroom->grade_level})"
            ], 422);
        }

        // 🔍 Check section match
        if ($student->student_section !== $classroom->section) {
            return response()->json([
                'message' => "Student section ({$student->student_section}) does not match class section ({$classroom->section})"
            ], 422);
        }

        // ✅ Check if already assigned
        if ($student->class_room_id) {
            return response()->json(['message' => 'Student already assigned to a class'], 409);
        }

        // ✅ Assign
        $student->class_room_id = $classroom->id;
        $student->save();

        $classroom->number_of_students = $classroom->students()->count();
        $classroom->save();

        return response()->json(['message' => 'Student assigned to class successfully']);
    }



    /**
     * Unassign a student from their class (teacher-only).
     */
    public function unassignStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $student = Student::with('classRoom')->findOrFail($request->student_id);

        if (!$student->classRoom || $student->classRoom->teacher_id !== $teacher->id) {
            return response()->json(['message' => 'You can only unassign students from your own class'], 403);
        }

        $classroom = $student->classRoom;

        $student->class_room_id = null;
        $student->save();

        // Update student count in class
        $classroom->number_of_students = $classroom->students()->count();
        $classroom->save();

        return response()->json([
            'message' => 'Student unassigned from class successfully',
            'student' => $student->fresh(), // just in case
        ]);
    }

    /**
     * Get all students assigned to a specific class.
     */
    public function getAssignedStudents($class_id)
    {
        $classroom = ClassRoom::with('students')->find($class_id);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        return response()->json([
            'class_id' => $classroom->id, // ✅ Added for Flutter expectation
            'students' => $classroom->students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_grade' => $student->student_grade,
                    'student_section' => $student->student_section,
                    'student_lrn' => $student->student_lrn,
                    'username' => optional($student->user)->username,
                    'profile_picture' => $student->profile_picture,
                    'class_room_id' => $student->class_room_id,
                ];
            })
        ]);
    }

    /**
     * Get all students who are not assigned to any class.
     */
    public function getUnassignedStudents()
    {
        $students = Student::whereNull('class_room_id')
            ->orWhere('class_room_id', 0)
            ->get();

        return response()->json([
            'unassigned_students' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_grade' => $student->student_grade,
                    'student_section' => $student->student_section,
                    'student_lrn' => $student->student_lrn,
                    'username' => optional($student->user)->username,
                    'profile_picture' => $student->profile_picture,
                    'class_room_id' => $student->class_room_id,
                ];
            })
        ]);
    }

    /**
     * Upload or update the background image for a class.
     */
    public function uploadBackground(Request $request, $classId)
    {
        $request->validate([
            'background_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $classroom = ClassRoom::findOrFail($classId);

        if (
            $classroom->background_image &&
            Storage::disk('public')->exists("class_backgrounds/{$classroom->background_image}")
        ) {
            Storage::disk('public')->delete("class_backgrounds/{$classroom->background_image}");
        }

        $fileName = time() . '_' . $request->file('background_image')->getClientOriginalName();
        $request->file('background_image')->storeAs('class_backgrounds', $fileName, 'public');

        $classroom->background_image = $fileName;
        $classroom->save();

        return response()->json([
            'message' => 'Background updated successfully',
            'background_image' => $fileName,
            'background_image_url' => asset("storage/class_backgrounds/$fileName"),
        ]);
    }

    /**
     * Get all classes assigned to the authenticated student.
     */
    public function getStudentClasses(Request $request)
    {
        \Log::info('🔥 getStudentClasses HIT', [
            'auth_id' => auth()->id(),
            'auth_user' => auth()->user()
        ]);

        // ✅ Fetch student by user_id
        $student = Student::where('user_id', auth()->id())->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        // ✅ Get all class IDs assigned to this student
        $classIds = Student::where('user_id', $student->user_id)
            ->whereNotNull('class_room_id')
            ->pluck('class_room_id')
            ->unique();


        $classes = ClassRoom::whereIn('id', $classIds)
            ->with('teacher') // Make sure the relationship is correct
            ->get();

        if ($classes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not assigned to any class',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $classes->map(function ($classroom) {
                $teacher = $classroom->teacher; // Teacher model
    
                return [
                    'id' => $classroom->id,
                    'class_name' => $classroom->class_name,
                    'grade_level' => $classroom->grade_level,
                    'section' => $classroom->section ?? 'N/A',
                    'school_year' => $classroom->school_year ?? 'N/A',
                    'classroom_code' => $classroom->classroom_code ?? 'N/A',

                    // ✅ Teacher Details (Dynamic)
                    'teacher_id' => $teacher ? $teacher->id : null,
                    'teacher_name' => $teacher->teacher_name ?? 'N/A',
                    'teacher_email' => $teacher->teacher_email ?? 'N/A',
                    'teacher_position' => $teacher->teacher_position ?? 'Teacher',
                    'teacher_avatar' => $teacher && $teacher->profile_picture
                        ? asset("{$teacher->profile_picture}")
                        : null,

                    // ✅ Class Background (Dynamic)
                    'background_image' => $classroom->background_image
                        ? asset("storage/class_backgrounds/{$classroom->background_image}")
                        : null,
                ];
            }),
        ]);
    }

    /**
     * Allow a student to join a class using a classroom code.
     * Returns the same response format as getStudentClasses.
     */
    public function joinClass(Request $request)
    {
        $request->validate([
            'classroom_code' => 'required|string|exists:class_rooms,classroom_code',
        ]);

        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Only students can join classes'
            ], 403);
        }

        $classroom = ClassRoom::with('teacher')
            ->where('classroom_code', $request->classroom_code)
            ->first();

        if (!$classroom) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid classroom code'
            ], 404);
        }

        // 🔍 Check grade level match
        if ($student->student_grade !== $classroom->grade_level) {
            return response()->json([
                'success' => false,
                'message' => 'Your grade does not match this classroom'
            ], 422);
        }

        // 🔍 Check section match
        if ($student->student_section !== $classroom->section) {
            return response()->json([
                'success' => false,
                'message' => 'Your section does not match this classroom'
            ], 422);
        }

        // 🔁 Already in this class
        if ($student->class_room_id == $classroom->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are already in this class'
            ], 409);
        }

        // 🚫 Already in another class
        if (!empty($student->class_room_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already assigned to another class'
            ], 409);
        }

        // ✅ Assign student
        $student->class_room_id = $classroom->id;
        $student->save();

        $classroom->number_of_students = $classroom->students()->count();
        $classroom->save();

        $teacher = $classroom->teacher;

        return response()->json([
            'success' => true,
            'message' => 'You have successfully joined the class',
            'class' => [
                'id' => $classroom->id,
                'class_name' => $classroom->class_name,
                'grade_level' => $classroom->grade_level,
                'section' => $classroom->section ?? 'N/A',
                'classroom_code' => $classroom->classroom_code ?? 'N/A',
                'teacher_name' => $teacher->teacher_name ?? 'N/A',
                'teacher_email' => $teacher->teacher_email ?? 'N/A',
                'teacher_position' => $teacher->teacher_position ?? 'Teacher',
                'teacher_avatar' => $teacher && $teacher->profile_picture
                    ? asset("storage/profile_images/{$teacher->profile_picture}")
                    : asset("storage/profile_images/default.png"),
                'background_image' => $classroom->background_image
                    ? asset("storage/class_backgrounds/{$classroom->background_image}")
                    : null,
            ],
        ], 200);
    }


    /**
     * Get tasks for the authenticated student based on their class grade.
     */
    public function getStudentTasks(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->with('classRoom') // Load class with grade_level
            ->firstOrFail();

        $grade = $student->classRoom->grade_level ?? null;

        if (!$grade) {
            return response()->json(['message' => 'Student is not assigned to any class'], 404);
        }

        // You can map grade to tasks (example mapping)
        $tasksByGrade = [
            '1' => ['Task 1', 'Task 2'],
            '2' => ['Task 3'],
            '3' => ['Task 4'],
            '4' => ['Task 5', 'Task 6', 'Task 7', 'Task 8', 'Task 9'],
            '5' => ['Task 10', 'Task 11', 'Task 12', 'Task 13'],
        ];

        $taskList = $tasksByGrade[$grade] ?? [];

        return response()->json([
            'grade_level' => $grade,
            'tasks' => $taskList,
        ]);
    }
}
