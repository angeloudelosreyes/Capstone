<?php

namespace App\Http\Controllers;

use App\Models\Subfolder;
use App\Models\UsersFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SubfolderController extends Controller
{
    /**
     * Store a new subfolder.
     */
    public function index()
    {
        $title = 'Drive Contents';

        // Fetch all root subfolders (with nested subfolders) and files
        $subfolders = Subfolder::whereNull('parent_folder_id')->with('subfolders')->get();
        $files = DB::table('files')->whereNull('folder_id')->get(); // Adjust query as needed

        return view('drive', compact('title', 'subfolders', 'files'));
    }



    public function store(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return back()->withErrors('User not authenticated.');
        }

        try {
            Log::info('Starting to create subfolder for user:', ['userId' => $userId]);

            // Decrypt `parent_id` if it's provided
            $parentId = $request->parent_id ? Crypt::decryptString($request->parent_id) : null;
            Log::info('Decrypted parent ID:', ['parent_id' => $parentId]);

            // Check if parent ID exists in the `subfolders` or `users_folder` table
            if ($parentId) {
                $isParentInSubfolders = DB::table('subfolders')->where('id', $parentId)->exists();
                $isParentInUsersFolder = DB::table('users_folder')->where('id', $parentId)->exists();

                if (!$isParentInSubfolders && !$isParentInUsersFolder) {
                    Log::error("Parent folder ID does not exist in users_folder or subfolders:", ['parent_id' => $parentId]);
                    return back()->withErrors('Selected parent folder does not exist.');
                }
            }

            // Validate the request
            $request->validate([
                'title' => 'required|string|max:255',
                'parent_id' => $parentId ? 'required' : 'nullable'
            ]);

            // Base path for the user's folders
            $basePath = 'users/' . $userId;
            $directory = '';

            // Determine if this is a root folder or a subfolder
            $isRootFolder = empty($parentId);

            // Construct the directory path based on whether it's a root or subfolder
            if ($isRootFolder) {
                $directory = $basePath . '/' . $request->title; // Use title for root folder
            } else {
                // Fetch the parent folder details
                if ($isParentInUsersFolder) {
                    $parentFolder = DB::table('users_folder')->where('id', $parentId)->first();
                    $directory = $basePath . '/' . $parentFolder->title . '/' . $request->title; // Include parent folder title
                } else {
                    // It's a subfolder, fetch the parent subfolder and grandparent folder details
                    $subfolder = DB::table('subfolders')->where('id', $parentId)->first();
                    $parentFolder = DB::table('users_folder')->where('id', $subfolder->parent_folder_id)->first();
                    $directory = $basePath . '/' . $parentFolder->title . '/' . $subfolder->name . '/' . $request->title; // Include subfolder name
                }
            }

            Log::info('Final directory path for subfolder storage:', ['directory' => $directory]);

            // Check if the directory already exists
            if (!Storage::disk('public')->exists($directory)) {
                // Create the directory in the storage
                Storage::disk('public')->makeDirectory($directory);
                Log::info('Directory successfully created:', ['directory' => $directory]);

                // Insert the subfolder into the `subfolders` table with subfolder_path
                DB::table('subfolders')->insert([
                    'user_id' => $userId,
                    'parent_folder_id' => $isRootFolder ? null : $parentId, // Set parent folder ID
                    'name' => $request->title,
                    'subfolder_path' => $directory, // Save the full path as subfolder_path
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return back()->with('message', 'Subfolder created successfully.');
            } else {
                Log::warning('Subfolder already exists:', ['directory' => $directory]);
                return back()->withErrors('Subfolder already exists.');
            }
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

        // Fetch the specific subfolder by ID
        $subfolder = Subfolder::find($decryptedId);

        if (!$subfolder) {
            return back()->with([
                'message' => 'Subfolder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Fetch nested subfolders and files related to the subfolder
        $nestedSubfolders = $subfolder->subfolders()->get(); // Fetch subfolders if any
        $files = DB::table('users_folder_files')
            ->where('subfolder_id', $decryptedId)
            ->get(); // Fetch files related to this subfolder

        return view('drive', [
            'title' => $subfolder->name,
            'subfolders' => $nestedSubfolders,
            'files' => $files,
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
    public function update(Request $request)
    {
        Log::info("Received request data for update:", $request->all());

        // Validate inputs
        $request->validate([
            'id' => 'required',
            'old' => 'required|string',
            'new' => 'required|string|max:255'
        ]);

        // Try decrypting the subfolder ID
        try {
            $subfolderId = Crypt::decryptString($request->id);
            Log::info("Decrypted subfolder ID:", ['id' => $subfolderId]);
        } catch (\Exception $e) {
            Log::error("Failed to decrypt subfolder ID:", ['error' => $e->getMessage()]);
            return back()->withErrors('Failed to decrypt subfolder ID.');
        }

        // Fetch subfolder information
        $subfolder = DB::table('subfolders')->where('id', $subfolderId)->first();
        if (!$subfolder) {
            Log::error("Subfolder not found for ID:", ['subfolder_id' => $subfolderId]);
            return back()->withErrors('Subfolder does not exist.');
        }

        // Build the full path for the old directory
        $basePath = 'users/' . auth()->user()->id; // Base path for the user
        $oldPath = $this->buildFullPath($subfolder->parent_folder_id, $basePath) . '/' . $request->old; // Build the old path
        $newPath = $this->buildFullPath($subfolder->parent_folder_id, $basePath) . '/' . $request->new; // Build the new path

        Log::info("Old Path: $oldPath, New Path: $newPath");

        // Check if the old path exists
        if (Storage::disk('public')->exists($oldPath)) {
            // Move the folder to the new path in storage
            Storage::disk('public')->move($oldPath, $newPath);
            Log::info("Folder moved in storage from $oldPath to $newPath");

            // Update the subfolder name in the database
            $update = DB::table('subfolders')->where('id', $subfolderId)->update(['name' => $request->new]);
            Log::info("Database update status:", ['update' => $update]);

            return back()->with([
                'message' => 'Subfolder has been renamed.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            Log::warning("Old subfolder path does not exist:", ['path' => $oldPath]);
            return back()->with([
                'message' => 'Old subfolder does not exist.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }

    private function buildFullPath($folderId, $basePath)
    {
        // Check if it's a main folder in `users_folder`
        $mainFolder = DB::table('users_folder')->where('id', $folderId)->first();
        if ($mainFolder) {
            // If it's the main folder, just return its path
            return $basePath . '/' . $mainFolder->title;
        }

        // Otherwise, assume it's a subfolder and try to retrieve it
        $subfolder = DB::table('subfolders')->where('id', $folderId)->first();
        if ($subfolder) {
            // Recursively call this function to get the parent path
            $parentPath = $this->buildFullPath($subfolder->parent_folder_id, $basePath);
            return $parentPath . '/' . $subfolder->name; // Append the current folder's name to the parent path
        }

        // If neither a main folder nor a subfolder was found, return null (invalid path)
        return null;
    }


    /**
     * Remove the specified subfolder from storage.
     */
    public function destroy(string $id)
    {
        // Decrypt the subfolder ID
        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (\Exception $e) {
            Log::error("Failed to decrypt subfolder ID:", ['error' => $e->getMessage()]);
            return back()->withErrors('Failed to decrypt subfolder ID.');
        }

        // Find the subfolder in the database
        $subfolder = Subfolder::find($decryptedId);

        if (!$subfolder) {
            return back()->with([
                'message' => 'Subfolder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Use the subfolder_path stored in the database for deletion
        $directory = $subfolder->subfolder_path;

        Log::info("Attempting to delete directory from storage:", ['directory' => $directory]);

        // Check if the directory exists and delete it from storage
        if (Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->deleteDirectory($directory);
            Log::info("Directory deleted from storage:", ['directory' => $directory]);
        } else {
            Log::warning("Directory not found in storage:", ['directory' => $directory]);
        }

        // Delete the subfolder entry from the database
        $subfolder->delete();

        return back()->with([
            'message' => 'Subfolder has been deleted.',
            'type' => 'success',
            'title' => 'System Notification'
        ]);
    }
}
