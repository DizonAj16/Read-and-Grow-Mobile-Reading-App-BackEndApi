<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaskPdfMaterial;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;

class TaskPdfMaterialController extends Controller
{
    // 🟢 Upload PDF
    public function store(Request $request)
    {
        $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'pdf_title' => 'required|string|max:255',
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // max 10MB
        ]);

        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found for current user'], 404);
        }

        $pdfPath = $request->file('pdf_file')->store('pdf_materials', 'public');

        $pdf = TaskPdfMaterial::create([
            'class_room_id' => $request->class_room_id,
            'teacher_id' => $teacher->id,
            'pdf_title' => $request->pdf_title,
            'pdf_file_path' => $pdfPath,
            'uploaded_at' => now(),
        ]);

        return response()->json([
            'message' => 'PDF uploaded successfully',
            'data' => $this->formatPdf($pdf)
        ], 201);
    }

    // 🟢 Get all PDFs for a classroom
    public function index(Request $request, $classRoomId)
    {
        $pdfs = TaskPdfMaterial::where('class_room_id', $classRoomId)
            ->with('teacher')
            ->get();

        return response()->json($pdfs->map(fn($pdf) => $this->formatPdf($pdf)));
    }

    // 🟢 Get PDFs by class ID
    public function getByClassroom($class_room_id)
    {
        $pdfs = TaskPdfMaterial::where('class_room_id', $class_room_id)
            ->with('teacher')
            ->get();

        return response()->json($pdfs->map(fn($pdf) => $this->formatPdf($pdf)));
    }

    // 🟢 Get PDFs of the logged-in student's class
    public function getMyClassroomPDFs(Request $request)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $pdfs = TaskPdfMaterial::where('class_room_id', $student->class_room_id)
            ->with('teacher')
            ->get();

        return response()->json($pdfs->map(fn($pdf) => $this->formatPdf($pdf)));
    }

    // 🗑️ Delete a PDF
    public function deletePdf(Request $request, $id)
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found'], 404);
        }

        $pdf = TaskPdfMaterial::where('id', $id)->where('teacher_id', $teacher->id)->first();

        if (!$pdf) {
            return response()->json(['error' => 'PDF not found or access denied'], 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($pdf->pdf_file_path)) {
            Storage::disk('public')->delete($pdf->pdf_file_path);
        }

        // Delete from database
        $pdf->delete();

        return response()->json(['message' => 'PDF deleted successfully']);
    }


    // 🔧 Format PDF path to full URL
    private function formatPdf($pdf)
    {
        return [
            'id' => $pdf->id,
            'class_room_id' => $pdf->class_room_id,
            'teacher_id' => $pdf->teacher_id,
            'teacher_name' => $pdf->teacher->teacher_name ?? 'Unknown Teacher',
            'pdf_title' => $pdf->pdf_title,
            'pdf_file_url' => asset('storage/' . $pdf->pdf_file_path),
            'uploaded_at' => $pdf->uploaded_at,
            'teacher' => $pdf->teacher,
        ];
    }

}
