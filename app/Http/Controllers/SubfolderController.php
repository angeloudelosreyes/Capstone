<?php

namespace App\Http\Controllers;

use App\Models\Subfolder;
use App\Models\UsersFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubfolderController extends Controller
{
    /**
     * Store a new subfolder.
     */
    public function index()
    {
        $title = 'Drive Contents';

        // Fetch subfolders and files
        $subfolders = Subfolder::whereNull('parent_folder_id')->get();
        $files = DB::table('files')->whereNull('folder_id')->get(); // Adjust query as needed

        // Pass both variables to the view
        return view('drive', compact('title', 'subfolders', 'files'));
    }



    public function store(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return back()->withErrors('User not authenticated.');
        }

        try {
            // Decrypt `parent_id` if it's encrypted
            $parentId = Crypt::decryptString($request->parent_id);

            // Merge decrypted `parent_id` into request for validation
            $request->merge(['parent_id' => $parentId]);

            // Validate the request with decrypted `parent_id`
            $request->validate([
                'title' => 'required|string|max:255',
                'parent_id' => 'required|exists:users_folder,id'
            ]);

            // Insert into subfolders table
            DB::table('subfolders')->insert([
                'user_id' => $userId,
                'parent_folder_id' => $parentId,
                'name' => $request->title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('message', 'Subfolder created successfully.');
        } catch (\Exception $e) {
            Log::error('Error inserting subfolder: ' . $e->getMessage());
            return back()->withErrors('Failed to create subfolder. Please try again.');
        }
    }
    /**
     * Display the specified subfolder.
     */
    public function show($id)
    {
        $decryptedId = Crypt::decryptString($id);

        // Fetch subfolders and files related to this folder
        $folder = UsersFolder::with(['subfolders', 'files'])->find($decryptedId);

        if (!$folder) {
            return back()->with([
                'message' => 'Folder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        return view('drive', [
            'title' => $folder->title,
            'subfolders' => $folder->subfolders, // Verify this returns data
            'files' => $folder->files, // Verify this returns data
            'folderId' => $id
        ]);
    }

    public function showSubfolders($parentId)
    {
        $subfolders = Subfolder::where('parent_folder_id', $parentId)
            ->with('files') // Assuming each subfolder has a 'files' relationship defined
            ->get();

        return view('subfolder', [
            'subfolders' => $subfolders,
            'parentFolderId' => $parentId // Pass the parent ID to the view
        ]);
    }
}
