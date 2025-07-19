<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Storage;
use Illuminate\Http\Request;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Models\Student;

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

        $class = ClassRoom::create([
            'teacher_id' => $teacher->id,
            'class_name' => $validated['class_name'],
            'grade_level' => $validated['grade_level'],
            'section' => $validated['section'] ?? null,
            'school_year' => $validated['school_year'] ?? null,
            // classroom_code and number_of_students handled in model
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
                    'section' => $class->section,
                    'school_year' => $class->school_year,
                    'student_count' => $class->students()->count(),
                    'teacher_name' => $class->teacher->teacher_name ?? 'Unknown',
                ];
            });

        return response()->json($classes);
    }




    /**
     * Optional: Show a specific classroom with students
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
            'section' => $classroom->section,
            'school_year' => $classroom->school_year,
            'student_count' => $classroom->students->count(),
            'teacher_name' => optional($classroom->teacher)->teacher_name ?? 'Unknown',
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



    public function assignStudent(Request $request)
    {

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_room_id' => 'required|exists:class_rooms,id',
        ]);

        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();
        $classroom = ClassRoom::where('id', $request->class_room_id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        if (!$classroom) {
            return response()->json(['message' => 'You do not own this class'], 403);
        }



        $student = Student::findOrFail($request->student_id);
        if ($student->class_room_id) {
            return response()->json(['message' => 'Student already assigned to a class'], 409);
        }

        $student->class_room_id = $classroom->id;
        $student->save();

        // Optional: update class student count
        $classroom->number_of_students = $classroom->students()->count();
        $classroom->save();

        return response()->json(['message' => 'Student assigned to class successfully']);
    }

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

        return response()->json(['message' => 'Student unassigned from class successfully']);
    }

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
    
    public function uploadBackground(Request $request, $classId)
{
    $request->validate([
        'background_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
    ]);

    $classroom = ClassRoom::findOrFail($classId);

    if ($classroom->background_image && 
        Storage::disk('public')->exists("class_backgrounds/{$classroom->background_image}")) {
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



}
