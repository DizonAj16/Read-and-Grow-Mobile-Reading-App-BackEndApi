<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StudentTaskProgress;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentTaskProgressController extends Controller
{
    /**
     * ✅ Store or update progress for a task (with audio submission)
     */
    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'correct_answers' => 'required|integer|min:0',
            'wrong_answers' => 'required|integer|min:0',
            'score' => 'required|numeric|min:0|max:100',
            'max_score' => 'required|numeric|min:0',
            'completed' => 'required|boolean',
            'audio_submitted' => 'required|boolean', // ✅ NEW
            'activity_details' => 'nullable|json', // ✅ Optional detailed activity scores
        ]);

        // ✅ Get the student_id from the logged-in user
        $authUserId = Auth::id();
        $student = \App\Models\Student::where('user_id', $authUserId)->first();

        if (!$student) {
            return response()->json(['message' => 'Student record not found'], 404);
        }

        $studentId = $student->id;
        $task = Task::findOrFail($request->task_id);

        // ✅ Find or create progress record
        $progress = StudentTaskProgress::firstOrNew([
            'student_id' => $studentId,
            'task_id' => $task->id
        ]);

        // ✅ Set attempts left on first creation
        if (!$progress->exists) {
            $progress->attempts_left = $task->max_attempts;
        }

        // ✅ Decrease attempts if not yet completed
        if (!$progress->completed && $progress->attempts_left > 0) {
            $progress->attempts_left -= 1;
        }

        // ✅ Update progress data
        $progress->correct_answers = $request->correct_answers;
        $progress->wrong_answers = $request->wrong_answers;
        $progress->score = $request->score;
        $progress->max_score = $request->max_score;
        $progress->completed = $request->completed;
        $progress->audio_submitted = $request->audio_submitted; // ✅ NEW
        $progress->activity_details = $request->activity_details ?? $progress->activity_details; // ✅ Optional JSON details

        $progress->save();

        return response()->json([
            'message' => 'Progress saved successfully',
            'progress' => $progress
        ]);
    }

    /**
     * ✅ Show all progress for a student (per grade or all)
     */
    public function showProgress($studentId)
    {
        $progress = StudentTaskProgress::with('task.grade')
            ->where('student_id', $studentId)
            ->get();

        return response()->json([
            'message' => 'Student progress retrieved successfully',
            'progress' => $progress
        ]);
    }

    /**
     * ✅ Reset attempts for testing or admin use
     */
    public function resetAttempts($student_id, $task_id)
    {
        $progress = StudentTaskProgress::where('student_id', $student_id)
            ->where('task_id', $task_id)
            ->first();

        if (!$progress) {
            return response()->json(['message' => 'Progress not found'], 404);
        }

        $progress->attempts_left = $progress->task->max_attempts ?? 3;
        $progress->save();

        return response()->json([
            'message' => 'Attempts reset successfully',
            'progress' => $progress
        ]);
    }
}
