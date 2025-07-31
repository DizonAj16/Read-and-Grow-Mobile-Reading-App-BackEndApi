<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\StudentTaskProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * ✅ Get tasks for a specific grade.
     * ✅ Includes locking logic based on attempts & completion.
     */
    public function index($gradeId)
    {
        $studentId = Auth::id(); // current logged-in student
        $tasks = Task::where('grade_id', $gradeId)
            ->orderBy('id', 'asc')
            ->get();

        $response = $tasks->map(function ($task) use ($studentId) {
            // Check student progress
            $progress = StudentTaskProgress::where('student_id', $studentId)
                ->where('task_id', $task->id)
                ->first();

            $isCompleted = $progress && $progress->is_completed;
            $attemptsLeft = $progress ? $progress->attempts_left : $task->max_attempts;

            return [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'max_attempts' => $task->max_attempts,
                'attempts_left' => $attemptsLeft,
                'is_completed' => $isCompleted,
                'is_locked' => $attemptsLeft <= 0,
            ];
        });

        return response()->json([
            'message' => 'Tasks retrieved successfully',
            'tasks' => $response
        ]);
    }
}
