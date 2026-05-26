<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileAccessGrant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    private $rootPath = 'projects';

    /**
     * Helper to check if user has access to a path
     */
    private function hasAccess($user, $path)
    {
        if ($user->role->name === 'admin' || $user->role->is_super_admin) {
            return true;
        }

        // Check if there is an exact grant for this path, or any parent path
        $parts = explode('/', $path);
        $currentCheck = '';
        foreach ($parts as $part) {
            $currentCheck = $currentCheck ? $currentCheck . '/' . $part : $part;
            
            // In our system, the grant path doesn't include 'projects/' prefix to make it simpler, 
            // or maybe it does? The root is 'projects'. The path given to API is relative to 'projects'.
            // Let's assume grants are stored relative to 'projects/'.
            $hasGrant = FileAccessGrant::where('user_id', $user->id)
                ->where('path', $currentCheck)
                ->exists();
                
            if ($hasGrant) return true;
        }

        return false;
    }

    public static function generateWorkspaceFile($user)
    {
        // Find all folders the user has access to
        $grants = FileAccessGrant::where('user_id', $user->id)->get();
        $basePath = '/home/coder/project';
        
        $folders = [];
        
        if ($user->role->name === 'admin' || $user->role->is_super_admin) {
            // Admins get the root projects folder
            $folders[] = [ "path" => $basePath ];
        } else {
            foreach ($grants as $grant) {
                $folders[] = [ "path" => $basePath . '/' . $grant->path ];
            }
            if (empty($folders)) {
                if (!Storage::disk('local')->exists('projects/.empty_' . $user->id)) {
                    Storage::disk('local')->makeDirectory('projects/.empty_' . $user->id);
                }
                $folders[] = [ "path" => $basePath . '/.empty_' . $user->id ];
            }
        }

        $workspaceContent = [
            "folders" => $folders,
            "settings" => []
        ];

        // Save the workspace file inside projects/.workspaces so it's accessible inside the container
        $workspaceDir = 'projects/.workspaces';
        if (!Storage::disk('local')->exists($workspaceDir)) {
            Storage::disk('local')->makeDirectory($workspaceDir);
        }

        Storage::disk('local')->put(
            $workspaceDir . '/' . $user->id . '.code-workspace', 
            json_encode($workspaceContent, JSON_PRETTY_PRINT)
        );
    }

    public function index(Request $request)
    {
        $currentPath = $request->query('path', '');
        $user = Auth::user();
        
        if (str_contains($currentPath, '..')) {
            abort(403, 'Invalid path');
        }

        // If not admin, verify access to the path they are trying to open
        if ($currentPath !== '' && !$this->hasAccess($user, $currentPath)) {
            abort(403, 'Unauthorized access to this folder');
        }

        $fullPath = $currentPath ? $this->rootPath . '/' . $currentPath : $this->rootPath;
        
        if (!Storage::disk('local')->exists($this->rootPath)) {
            Storage::disk('local')->makeDirectory($this->rootPath);
        }

        $dirs = Storage::disk('local')->directories($fullPath);
        $files = Storage::disk('local')->files($fullPath);

        $directories = [];
        foreach ($dirs as $dir) {
            $relativePath = str_replace($this->rootPath . '/', '', $dir);
            
            // Hide .workspaces folder
            if (str_starts_with(basename($dir), '.')) continue;

            // Strict check: always check if they have access to this directory
            if (!$this->hasAccess($user, $relativePath)) {
                continue;
            }

            $directories[] = [
                'id' => $relativePath,
                'name' => basename($dir)
            ];
        }

        $fileNodes = [];
        foreach ($files as $file) {
            $relativePath = str_replace($this->rootPath . '/', '', $file);
            
            if (str_starts_with(basename($file), '.')) continue;

            // Strict check: always check if they have access to this file specifically
            // (or its parent directory, handled by hasAccess)
            if (!$this->hasAccess($user, $relativePath)) {
                continue;
            }

            $fileNodes[] = [
                'id' => $relativePath,
                'original_name' => basename($file),
                'mime_type' => Storage::disk('local')->mimeType($file),
                'size' => Storage::disk('local')->size($file),
                'created_at' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file))
            ];
        }

        return response()->json([
            'directories' => $directories,
            'files' => $fileNodes
        ]);
    }

    // -------------------------------------------------------------
    // CHUNKED UPLOADS LOGIC
    // -------------------------------------------------------------
    
    public function chunkStatus(Request $request)
    {
        $identifier = $request->query('identifier');
        $chunkDir = 'chunks/' . $identifier;

        if (!Storage::disk('local')->exists($chunkDir)) {
            return response()->json(['uploaded_chunks' => []]);
        }

        $files = Storage::disk('local')->files($chunkDir);
        $uploadedChunks = [];
        foreach ($files as $file) {
            $base = basename($file);
            if (!str_ends_with($base, '.part')) {
                $uploadedChunks[] = (int) $base;
            }
        }

        return response()->json(['uploaded_chunks' => $uploadedChunks]);
    }

    public function uploadChunk(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'identifier' => 'required|string',
            'chunk_index' => 'required|integer',
        ]);

        $identifier = $request->input('identifier');
        $chunkIndex = $request->input('chunk_index');
        $chunkDir = 'chunks/' . $identifier;

        if (!Storage::disk('local')->exists($chunkDir)) {
            Storage::disk('local')->makeDirectory($chunkDir);
        }

        $tempName = (string) $chunkIndex . '.part';
        $finalName = (string) $chunkIndex;

        // Write as .part first
        Storage::disk('local')->putFileAs($chunkDir, $request->file('file'), $tempName);
        
        // Once successfully written 100%, rename to final chunk integer
        Storage::disk('local')->move($chunkDir . '/' . $tempName, $chunkDir . '/' . $finalName);

        return response()->json(['message' => 'Chunk uploaded']);
    }

    public function uploadComplete(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'original_name' => 'required|string',
            'total_chunks' => 'required|integer',
            'path' => 'nullable|string',
            'relative_path' => 'nullable|string',
        ]);

        $identifier = $request->input('identifier');
        $originalName = $request->input('original_name');
        $totalChunks = $request->input('total_chunks');
        $currentPath = $request->input('path', '');
        $relativePath = $request->input('relative_path', '');
        $user = Auth::user();

        // RBAC Check
        if (str_contains($currentPath, '..') || str_contains($relativePath, '..') || ($currentPath !== '' && !$this->hasAccess($user, $currentPath))) {
            abort(403, 'Unauthorized');
        }

        $chunkDir = 'chunks/' . $identifier;
        
        // Verify all chunks exist
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!Storage::disk('local')->exists($chunkDir . '/' . $i)) {
                abort(400, 'Missing chunk ' . $i);
            }
        }

        // Determine target path
        $targetDir = $currentPath ? $this->rootPath . '/' . $currentPath : $this->rootPath;
        if ($relativePath) {
            $dirname = dirname($relativePath);
            if ($dirname !== '.') {
                $targetDir .= '/' . $dirname;
            }
        }
        
        if (!Storage::disk('local')->exists($targetDir)) {
            Storage::disk('local')->makeDirectory($targetDir);
        }

        $finalPath = Storage::disk('local')->path($targetDir . '/' . $originalName);

        // Merge chunks
        $out = @fopen($finalPath, "wb");
        if ($out) {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = Storage::disk('local')->path($chunkDir . '/' . $i);
                $in = @fopen($chunkPath, "rb");
                if ($in) {
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    fclose($in);
                }
            }
            fclose($out);
        }

        // Cleanup chunks
        Storage::disk('local')->deleteDirectory($chunkDir);

        // Auto-grant access to the creator if at root
        if ($currentPath === '' && $user->role->name !== 'admin') {
            $grantPath = $relativePath ? explode('/', $relativePath)[0] : $originalName;
            FileAccessGrant::firstOrCreate([
                'user_id' => $user->id,
                'path' => $grantPath
            ]);
            $this->generateWorkspaceFile($user);
        }

        return response()->json(['message' => 'File merge completed successfully'], 201);
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'path' => 'nullable|string'
        ]);

        $folderName = $request->input('name');
        $currentPath = $request->input('path', '');
        $user = Auth::user();

        if (str_contains($currentPath, '..') || str_contains($folderName, '..') || ($currentPath !== '' && !$this->hasAccess($user, $currentPath))) {
            abort(403, 'Unauthorized');
        }

        $targetDir = $currentPath ? $this->rootPath . '/' . $currentPath . '/' . $folderName : $this->rootPath . '/' . $folderName;
        Storage::disk('local')->makeDirectory($targetDir);

        if ($currentPath === '' && $user->role->name !== 'admin') {
            FileAccessGrant::firstOrCreate([
                'user_id' => $user->id,
                'path' => $folderName
            ]);
            $this->generateWorkspaceFile($user);
        }

        return response()->json(['message' => 'Folder created successfully'], 201);
    }

    public function deleteItem(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $relativePath = $request->input('path');
        $user = Auth::user();

        if (str_contains($relativePath, '..') || !$this->hasAccess($user, $relativePath)) {
            abort(403, 'Unauthorized');
        }

        $fullPath = $this->rootPath . '/' . $relativePath;
        if (!Storage::disk('local')->exists($fullPath)) {
            abort(404, 'Item not found');
        }

        $isDirectory = Storage::disk('local')->directoryExists($fullPath);
        $trashName = uniqid() . '_' . basename($relativePath);
        $trashPath = $this->rootPath . '/.trash/' . $trashName;

        if (!Storage::disk('local')->exists($this->rootPath . '/.trash')) {
            Storage::disk('local')->makeDirectory($this->rootPath . '/.trash');
        }

        // Move to trash
        $moved = Storage::disk('local')->move($fullPath, $trashPath);
        
        if (!$moved) {
            abort(500, 'Failed to move to recycle bin. The file/folder may be locked or in use by the Code Editor.');
        }

        // Record in DB
        \App\Models\TrashedFile::create([
            'user_id' => $user->id,
            'original_path' => $relativePath,
            'trash_path' => $trashPath,
            'is_directory' => $isDirectory
        ]);

        // Revoke any access grants for this path and subpaths
        FileAccessGrant::where('path', $relativePath)->orWhere('path', 'like', $relativePath . '/%')->delete();
        $this->generateWorkspaceFile($user);

        return response()->json(['message' => 'Moved to trash']);
    }

    public function copyItem(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'destination' => 'nullable|string'
        ]);

        $source = $request->input('source');
        $dest = $request->input('destination', '');
        $user = Auth::user();

        if (str_contains($source, '..') || str_contains($dest, '..') || !$this->hasAccess($user, $source)) {
            abort(403, 'Unauthorized source');
        }
        if ($dest !== '' && !$this->hasAccess($user, $dest)) {
            abort(403, 'Unauthorized destination');
        }

        $sourceFullPath = $this->rootPath . '/' . $source;
        $destFullPath = $dest ? $this->rootPath . '/' . $dest . '/' . basename($source) : $this->rootPath . '/' . basename($source);

        if (!Storage::disk('local')->exists($sourceFullPath)) {
            abort(404, 'Source not found');
        }

        // Copy doesn't exist natively for directories in Storage, we might need to write a recursive copy if directory
        if (Storage::disk('local')->directoryExists($sourceFullPath)) {
            // Very simple recursive copy approximation
            $files = Storage::disk('local')->allFiles($sourceFullPath);
            $dirs = Storage::disk('local')->allDirectories($sourceFullPath);
            
            Storage::disk('local')->makeDirectory($destFullPath);
            foreach ($dirs as $dir) {
                $rel = str_replace($sourceFullPath . '/', '', $dir);
                Storage::disk('local')->makeDirectory($destFullPath . '/' . $rel);
            }
            foreach ($files as $file) {
                $rel = str_replace($sourceFullPath . '/', '', $file);
                Storage::disk('local')->copy($file, $destFullPath . '/' . $rel);
            }
        } else {
            Storage::disk('local')->copy($sourceFullPath, $destFullPath);
        }

        return response()->json(['message' => 'Item copied']);
    }

    public function moveItem(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'destination' => 'nullable|string'
        ]);

        $source = $request->input('source');
        $dest = $request->input('destination', '');
        $user = Auth::user();

        if (str_contains($source, '..') || str_contains($dest, '..') || !$this->hasAccess($user, $source)) {
            abort(403, 'Unauthorized source');
        }
        if ($dest !== '' && !$this->hasAccess($user, $dest)) {
            abort(403, 'Unauthorized destination');
        }

        $sourceFullPath = $this->rootPath . '/' . $source;
        $destFullPath = $dest ? $this->rootPath . '/' . $dest . '/' . basename($source) : $this->rootPath . '/' . basename($source);

        if (!Storage::disk('local')->exists($sourceFullPath)) {
            abort(404, 'Source not found');
        }

        Storage::disk('local')->move($sourceFullPath, $destFullPath);

        // Update grants: if they move it, we might need to update the path in FileAccessGrants
        $grants = FileAccessGrant::where('path', $source)->orWhere('path', 'like', $source . '/%')->get();
        foreach ($grants as $grant) {
            $newPath = str_replace($source, ($dest ? $dest . '/' . basename($source) : basename($source)), $grant->path);
            $grant->update(['path' => $newPath]);
        }
        $this->generateWorkspaceFile($user);

        return response()->json(['message' => 'Item moved']);
    }

    public function getTrash(Request $request)
    {
        $user = Auth::user();
        $query = \App\Models\TrashedFile::query();
        
        if ($user->role->name !== 'admin' && !$user->role->is_super_admin) {
            $query->where('user_id', $user->id);
        }
        
        $files = $query->with('user')->orderBy('created_at', 'desc')->get();
        return response()->json(['trashed' => $files]);
    }

    public function restoreTrash(Request $request, $id)
    {
        $user = Auth::user();
        $trashed = \App\Models\TrashedFile::findOrFail($id);

        if ($user->role->name !== 'admin' && !$user->role->is_super_admin && $trashed->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $destPath = $this->rootPath . '/' . $trashed->original_path;
        
        // Ensure parent directory exists
        $parentDir = dirname($destPath);
        if (!Storage::disk('local')->exists($parentDir)) {
            Storage::disk('local')->makeDirectory($parentDir);
        }

        if (Storage::disk('local')->exists($trashed->trash_path)) {
            Storage::disk('local')->move($trashed->trash_path, $destPath);
        }

        $trashed->delete();

        // Restore basic grant if root
        if (!str_contains($trashed->original_path, '/')) {
            FileAccessGrant::firstOrCreate([
                'user_id' => $trashed->user_id,
                'path' => $trashed->original_path
            ]);
            $this->generateWorkspaceFile($trashed->user);
        }

        return response()->json(['message' => 'Restored successfully']);
    }

    public function preview(Request $request): StreamedResponse
    {
        $path = $request->query('path');
        $user = Auth::user();
        
        if (!$path || str_contains($path, '..') || !$this->hasAccess($user, $path)) {
            abort(403, 'Unauthorized');
        }

        $fullPath = $this->rootPath . '/' . $path;

        if (!Storage::disk('local')->exists($fullPath)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::disk('local')->response($fullPath, basename($fullPath));
    }

    // RBAC MANAGEMENT ENDPOINTS
    public function getAccess(Request $request)
    {
        $path = $request->query('path');
        $user = Auth::user();

        // Only admins can view/manage access grants for now
        if ($user->role->name !== 'admin' && !$user->role->is_super_admin) {
            abort(403, 'Only admins can manage access');
        }

        $grants = FileAccessGrant::where('path', $path)->with('user:id,name,email,avatar')->get();
        $allUsers = User::with('role')->get();

        return response()->json([
            'grants' => $grants,
            'allUsers' => $allUsers
        ]);
    }

    public function setAccess(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'user_ids' => 'required|array', // array of user IDs that should have access
        ]);

        $path = $request->input('path');
        $userIds = $request->input('user_ids');
        $currentUser = Auth::user();

        if ($currentUser->role->name !== 'admin' && !$currentUser->role->is_super_admin) {
            abort(403, 'Only admins can manage access');
        }

        // Remove old grants
        FileAccessGrant::where('path', $path)->delete();

        // Add new grants
        foreach ($userIds as $uid) {
            FileAccessGrant::create([
                'user_id' => $uid,
                'path' => $path,
                'access_level' => 'read_write'
            ]);
            
            // Regenerate workspace file for this user
            $targetUser = User::find($uid);
            if ($targetUser) {
                $this->generateWorkspaceFile($targetUser);
            }
        }

        // Also regenerate for users who lost access
        $allUsers = User::all();
        foreach ($allUsers as $u) {
            if (!in_array($u->id, $userIds)) {
                // If they are admin, they have access anyway, but let's just regenerate for everyone safely
                $this->generateWorkspaceFile($u);
            }
        }

        return response()->json(['message' => 'Access updated successfully']);
    }
}
