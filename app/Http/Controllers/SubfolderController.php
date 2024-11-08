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
            Log::info('Decrypted parent ID (for users_folder):', ['parent_id' => $parentId]);

            // Check if parent ID exists in the `users_folder` table (not `subfolders`)
            if ($parentId && !DB::table('users_folder')->where('id', $parentId)->exists()) {
                Log::error("Parent folder ID does not exist in users_folder:", ['parent_id' => $parentId]);
                return back()->withErrors('Selected parent folder does not exist.');
            }

            // Determine if this is a root folder or a subfolder
            $isRootFolder = empty($parentId);

            // Validate the request
            $request->validate([
                'title' => 'required|string|max:255',
                'parent_id' => $isRootFolder ? 'nullable' : 'required'
            ]);

            // Define the directory path under `storage/app/public`
            $basePath = 'users/' . $userId;
            $directory = $isRootFolder ?
                $basePath . '/' . $request->title :
                $basePath . '/' . DB::table('users_folder')->where('id', $parentId)->value('title') . '/' . $request->title;

            Log::info('Calculated directory path for subfolder:', ['directory' => $directory]);

            // Check if the directory already exists
            if (!Storage::disk('public')->exists($directory)) {
                // Create the directory in the storage
                $directoryCreated = Storage::disk('public')->makeDirectory($directory);
                if ($directoryCreated) {
                    Log::info('Directory successfully created:', ['directory' => $directory]);
                } else {
                    Log::error('Failed to create directory:', ['directory' => $directory]);
                    return back()->withErrors('Failed to create the storage directory.');
                }

                // Insert the subfolder into the `subfolders` table
                $inserted = DB::table('subfolders')->insert([
                    'user_id' => $userId,
                    'parent_folder_id' => $isRootFolder ? null : $parentId, // now referencing `users_folder`
                    'name' => $request->title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted) {
                    Log::info('Subfolder successfully inserted into database:', ['title' => $request->title]);
                } else {
                    Log::error('Failed to insert subfolder into database.');
                    return back()->withErrors('Database insert failed.');
                }

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

        // Fetch subfolder and parent folder information
        $subfolder = DB::table('subfolders')->where('id', $subfolderId)->first();
        $parentFolder = DB::table('users_folder')->where('id', $subfolder->parent_folder_id)->value('title');

        if (!$parentFolder) {
            Log::error("Parent folder not found for subfolder ID:", ['subfolder_id' => $subfolderId]);
            return back()->withErrors('Parent folder does not exist.');
        }

        // Define paths for renaming the folder within `storage/app/public`
        $oldPath = 'users/' . auth()->user()->id . '/' . $parentFolder . '/' . $request->old;
        $newPath = 'users/' . auth()->user()->id . '/' . $parentFolder . '/' . $request->new;
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



    /**
     * Remove the specified subfolder from storage.
     */
    public function destroy(string $id)
    {
        // Decrypt the subfolder ID
        $decryptedId = Crypt::decryptString($id);

        // Find the subfolder in the database
        $subfolder = Subfolder::find($decryptedId);

        if (!$subfolder) {
            return back()->with([
                'message' => 'Subfolder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Fetch the parent folder's name from the `users_folder` table
        $parentFolder = DB::table('users_folder')->where('id', $subfolder->parent_folder_id)->value('title');

        if (!$parentFolder) {
            Log::error("Parent folder not found for subfolder ID:", ['subfolder_id' => $decryptedId]);
            return back()->withErrors('Parent folder does not exist.');
        }

        // Define the full directory path including the parent folder
        $directory = 'users/' . auth()->user()->id . '/' . $parentFolder . '/' . $subfolder->name;
        Log::info("Attempting to delete directory:", ['directory' => $directory]);

        // Check if the directory exists and delete it from cloud storage
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
