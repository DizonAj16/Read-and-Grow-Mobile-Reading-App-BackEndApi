<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaskMaterial;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TaskMaterialController extends Controller
{
    // Supported file types and their validation rules
    private $fileTypeConfig = [
        'pdf' => [
            'mimes' => 'pdf',
            'max_size' => 30240, // 10MB
            'icon' => 'description',
            'type' => 'document'
        ],
        'image' => [
            'mimes' => 'jpg,jpeg,png,gif,bmp,webp,svg',
            'max_size' => 10240, // 5MB
            'icon' => 'image',
            'type' => 'image'
        ],
        'video' => [
            'mimes' => 'mp4,avi,mov,wmv,flv,mkv,webm',
            'max_size' => 100000, // 50MB
            'icon' => 'videocam',
            'type' => 'video'
        ],
        'audio' => [
            'mimes' => 'mp3,wav,ogg,m4a,aac,M4A', // Added M4A for case sensitivity
            'max_size' => 30000, // 30MB (increased from 10MB)
            'icon' => 'audiotrack',
            'type' => 'audio'
        ],
        'document' => [
            'mimes' => 'doc,docx,ppt,pptx,xls,xlsx,txt,rtf',
            'max_size' => 30240, // 10MB
            'icon' => 'article',
            'type' => 'document'
        ],
        'archive' => [
            'mimes' => 'zip,rar,7z',
            'max_size' => 20480, // 20MB
            'icon' => 'folder',
            'type' => 'archive'
        ]
    ];

    // 🟢 Upload any educational material
// 🟢 Upload any educational material
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_room_id' => 'required|exists:class_rooms,id',
            'material_title' => 'required|string|max:255',
            'material_file' => 'required|file',
            'material_type' => 'sometimes|string|in:pdf,image,video,audio,document,archive',
            'description' => 'nullable|string|max:1000', // Added description field
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found for current user'], 404);
        }

        $file = $request->file('material_file');
        $fileType = $this->determineFileType($file, $request->input('material_type'));

        if (!$fileType) {
            return response()->json(['error' => 'Unsupported file type'], 415);
        }

        $config = $this->fileTypeConfig[$fileType];

        // Validate file against its specific rules using proper MIME type validation
        $fileValidator = Validator::make(
            ['material_file' => $file],
            $this->getFileValidationRules($fileType)
        );

        if ($fileValidator->fails()) {
            return response()->json([
                'error' => 'File validation failed',
                'details' => $fileValidator->errors(),
                'detected_type' => $fileType,
                'file_extension' => strtolower($file->getClientOriginalExtension())
            ], 422);
        }

        // Store file in appropriate directory
        $filePath = $file->store("task_materials/{$fileType}", 'public');

        $material = TaskMaterial::create([
            'class_room_id' => $request->class_room_id,
            'teacher_id' => $teacher->id,
            'material_title' => $request->material_title,
            'material_file_path' => $filePath,
            'material_type' => $fileType,
            'file_size' => $file->getSize(),
            'description' => $request->input('description'), // Save description
            'uploaded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Material uploaded successfully',
            'data' => $this->formatMaterial($material)
        ], 201);
    }



    // 🔧 Get proper file validation rules for each type
    private function getFileValidationRules($fileType)
    {
        $config = $this->fileTypeConfig[$fileType];

        // For audio files, use a more flexible validation approach
        if ($fileType === 'audio') {
            return [
                'material_file' => [
                    'required',
                    'file',
                    'max:' . $config['max_size'],
                    function ($attribute, $value, $fail) use ($config) {
                        $extension = strtolower($value->getClientOriginalExtension());
                        $allowedExtensions = array_map('strtolower', explode(',', $config['mimes']));

                        if (!in_array($extension, $allowedExtensions)) {
                            $fail("The $attribute must be a file of type: " . $config['mimes']);
                        }

                        // Additional MIME type validation for audio files
                        $mimeType = $value->getMimeType();
                        $allowedMimeTypes = [
                            'audio/mpeg',      // mp3
                            'audio/wav',       // wav
                            'audio/ogg',       // ogg
                            'audio/x-m4a',     // m4a
                            'audio/mp4',       // m4a, aac
                            'audio/aac',       // aac
                        ];

                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            $fail("The $attribute has an invalid MIME type: $mimeType");
                        }
                    }
                ]
            ];
        }

        // For other file types, use standard validation
        return [
            'material_file' => "required|file|mimes:{$config['mimes']}|max:{$config['max_size']}"
        ];
    }

    // 🔧 Determine file type based on extension or explicit type
    private function determineFileType($file, $explicitType = null)
    {
        if ($explicitType && array_key_exists($explicitType, $this->fileTypeConfig)) {
            return $explicitType;
        }

        $extension = strtolower($file->getClientOriginalExtension());

        foreach ($this->fileTypeConfig as $type => $config) {
            $allowedExtensions = array_map('strtolower', explode(',', $config['mimes']));
            if (in_array($extension, $allowedExtensions)) {
                return $type;
            }
        }

        return null;
    }

    // 🟢 Get all materials for a classroom
    public function index(Request $request, $classRoomId)
    {
        $materials = TaskMaterial::where('class_room_id', $classRoomId)
            ->with('teacher')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($materials->map(fn($material) => $this->formatMaterial($material)));
    }

    // 🟢 Get materials by class ID
    public function getByClassroom($class_room_id)
    {
        $materials = TaskMaterial::where('class_room_id', $class_room_id)
            ->with('teacher')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($materials->map(fn($material) => $this->formatMaterial($material)));
    }

    // 🟢 Get materials of the logged-in student's class
    public function getMyClassroomMaterials(Request $request)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $materials = TaskMaterial::where('class_room_id', $student->class_room_id)
            ->with('teacher')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($materials->map(fn($material) => $this->formatMaterial($material)));
    }

    // 🟢 Get materials filtered by type
    public function getByType(Request $request, $class_room_id, $type)
    {
        if (!array_key_exists($type, $this->fileTypeConfig)) {
            return response()->json(['error' => 'Invalid material type'], 400);
        }

        $materials = TaskMaterial::where('class_room_id', $class_room_id)
            ->where('material_type', $type)
            ->with('teacher')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($materials->map(fn($material) => $this->formatMaterial($material)));
    }

    // 🗑️ Delete a material
    public function deleteMaterial(Request $request, $id)
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found'], 404);
        }

        $material = TaskMaterial::where('id', $id)->where('teacher_id', $teacher->id)->first();

        if (!$material) {
            return response()->json(['error' => 'Material not found or access denied'], 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($material->material_file_path)) {
            Storage::disk('public')->delete($material->material_file_path);
        }

        // Delete from database
        $material->delete();

        return response()->json(['message' => 'Material deleted successfully']);
    }

    // 🔧 Format material with full URL and metadata
    private function formatMaterial($material)
    {
        $config = $this->fileTypeConfig[$material->material_type] ?? [
            'icon' => 'insert_drive_file',
            'type' => 'unknown'
        ];

        return [
            'id' => $material->id,
            'class_room_id' => $material->class_room_id,
            'teacher_id' => $material->teacher_id,
            'teacher_name' => $material->teacher->teacher_name ?? 'Unknown Teacher',
            'material_title' => $material->material_title,
            'material_file_url' => asset('storage/' . $material->material_file_path),
            'material_type' => $material->material_type,
            'file_icon' => $config['icon'],
            'file_size' => $this->formatFileSize($material->file_size),
            'description' => $material->description, // Include description in response
            'uploaded_at' => $material->uploaded_at,
            'teacher' => $material->teacher,
        ];
    }

    // 🔧 Format file size to human readable format
    private function formatFileSize($bytes)
    {
        if ($bytes == 0)
            return '0 Bytes';

        $units = ['Bytes', 'KB', 'MB', 'GB'];
        $base = log($bytes) / log(1024);
        $floor = floor($base);

        return round(pow(1024, $base - $floor), 2) . ' ' . $units[$floor];
    }

    // 🟢 Get supported file types and their constraints
    public function getSupportedTypes()
    {
        $types = [];
        foreach ($this->fileTypeConfig as $type => $config) {
            $types[$type] = [
                'mimes' => $config['mimes'],
                'max_size' => $config['max_size'],
                'icon' => $config['icon'],
                'type' => $config['type']
            ];
        }

        return response()->json($types);
    }
}