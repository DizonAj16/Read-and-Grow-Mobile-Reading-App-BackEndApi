<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    /**
     * ✅ Get all grades with basic details
     */
    public function index()
    {
        $grades = Grade::select('id', 'name', 'level')
            ->orderBy('level', 'asc')
            ->get();

        return response()->json([
            'message' => 'Grades retrieved successfully',
            'grades' => $grades
        ], 200);
    }
}
