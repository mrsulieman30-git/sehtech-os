<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\FileNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Get directory contents (folders and files)
     */
    public function index(Request $request)
    {
        $directoryId = $request->query('directory_id');

        // Note: For a true production app, we would inject a Policy check here 
        // to verify the user has access to this specific department/directory.

        $directories = Directory::where('parent_id', $directoryId)
            ->with('creator:id,name,avatar')
            ->orderBy('name')
            ->get();

        $files = FileNode::where('directory_id', $directoryId)
            ->where('is_deleted', false)
            ->with('creator:id,name,avatar')
            ->orderBy('name')
            ->get();

        return response()->json([
            'directories' => $directories,
            'files' => $files
        ]);
    }

    /**
     * Handle file upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max per spec rules
            'directory_id' => 'nullable|uuid|exists:directories,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
        ]);

        $uploadedFile = $request->file('file');
        $user = Auth::user();
        
        $originalName = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension();
        $mimeType = $uploadedFile->getMimeType();
        $size = $uploadedFile->getSize();
        
        // Generate a secure, unique path
        $securePath = 'cfs/' . date('Y/m/') . Str::uuid() . '.' . $extension;
        
        // Store the file on the local disk (outside public webroot for security)
        $disk = config('filesystems.default', 'local');
        Storage::disk($disk)->put($securePath, file_get_contents($uploadedFile));

        $fileNode = FileNode::create([
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'path' => $securePath,
            'disk' => $disk,
            'mime_type' => $mimeType,
            'size' => $size,
            'directory_id' => $request->input('directory_id'),
            'department_id' => $request->input('department_id'),
            'access_level' => 'private', // Default, can be updated via UI
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $fileNode->load('creator:id,name,avatar')
        ], 201);
    }

    /**
     * Preview / Stream the file
     */
    public function preview($id): StreamedResponse
    {
        $fileNode = FileNode::findOrFail($id);

        // Policy Check would go here in production
        
        if (!Storage::disk($fileNode->disk)->exists($fileNode->path)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::disk($fileNode->disk)->response($fileNode->path, $fileNode->original_name);
    }
}
