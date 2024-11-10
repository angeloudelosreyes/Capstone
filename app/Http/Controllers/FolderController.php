<?php

namespace App\Http\Controllers;

use App\Models\Subfolder;
use App\Models\UsersFolder;
use App\Models\UsersFolderFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class FolderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch only top-level folders, excluding subfolders
        $query = UsersFolder::whereNull('parent_folder_id')->paginate(10); // Adjust as needed

        return view('home', compact('query'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required']
        ], [
            'title.required' => 'This field is required'
        ]);

        // Define the directory path
        $directory = 'public/users/' . auth()->user()->id . '/' . $request->title;

        // Check if the directory already exists
        if (!Storage::exists($directory)) {
            // Create the directory if it doesn't exist
            Storage::makeDirectory($directory);

            // Insert the folder record, including the folder_path
            DB::table('users_folder')->insert([
                'users_id' => auth()->user()->id,
                'title' => $request->title,
                'folder_path' => $directory, // Save the folder path
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with([
                'message' => 'New folder has been created.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'Folder already exists.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $decryptedId = Crypt::decryptString($id);

        // Fetch the folder and related subfolders and files
        $folder = UsersFolder::find($decryptedId);

        if (!$folder) {
            return back()->with([
                'message' => 'Folder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Paginate subfolders and files separately
        $subfolders = Subfolder::where('parent_folder_id', $decryptedId)->paginate(10, ['*'], 'subfolders');
        $files = UsersFolderFile::where('users_folder_id', $decryptedId)->paginate(10, ['*'], 'files');

        return view('drive', [
            'title' => $folder->title,
            'subfolders' => $subfolders,
            'files' => $files,
            'folderId' => $id
        ]);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $old = 'public/users/' . auth()->user()->id . '/' . $request->old;
        $new = 'public/users/' . auth()->user()->id . '/' . $request->new;
        if (Storage::exists($old)) {
            // Storage:move()  eto yong ginagamit para ma move mo anywhere sa file system mo yong file na ginagamit mo.
            Storage::move($old, $new);
            DB::table('users_folder')->where(['id' => Crypt::decryptString($request->id)])->update(['title' => $request->new]);
            return back()->with([
                'message' => 'Folder has been renamed.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'Old folder does not exist.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encryptedId)
    {
        Log::info("Starting destroy function for folder ID: $encryptedId");

        DB::beginTransaction();

        try {
            // Decrypt the folder ID
            $folderId = Crypt::decryptString($encryptedId);
            Log::info("Decrypted folder ID: $folderId");

            // Find the folder to be deleted
            $folder = UsersFolder::findOrFail($folderId);
            Log::info("Folder found: " . $folder->title);

            // Get the folder path from the database
            $folderPath = str_replace('public/', '', $folder->folder_path); // Ensure 'public/' is not included
            Log::info("Adjusted folder path for storage deletion: $folderPath");

            // Delete all subfolders and their files
            $this->deleteSubfoldersAndFilesIteratively($folder->id);
            Log::info("Deleted all subfolders and files for folder ID: $folderId");

            // Delete all files directly in the folder
            UsersFolderFile::where('users_folder_id', $folder->id)->delete();
            Log::info("Deleted all files in the main folder ID: $folderId");

            // Delete the main folder record in the database
            $folder->delete();
            Log::info("Main folder deleted: $folderId");

            // Delete the folder from storage
            if (Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->deleteDirectory($folderPath);
                Log::info("Deleted folder directory from storage: $folderPath");
            } else {
                Log::warning("Folder path does not exist in storage: $folderPath");
            }

            DB::commit();
            Log::info("Transaction committed successfully for folder ID: $folderId");

            return back()->with([
                'message' => 'Folder and all associated subfolders and files have been deleted successfully.',
                'type' => 'success',
                'title' => 'System Notification'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting folder and associated items: " . $e->getMessage());

            return back()->with([
                'message' => 'Error deleting folder and associated items.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }



    /**
     * Iteratively delete all subfolders and their files for a given parent folder ID.
     */
    private function deleteSubfoldersAndFilesIteratively($parentFolderId)
    {
        Log::info("Deleting subfolders and files iteratively for parent folder ID: $parentFolderId");

        // Queue for subfolders to delete
        $foldersToDelete = [$parentFolderId];

        while (!empty($foldersToDelete)) {
            $currentFolderId = array_pop($foldersToDelete);
            Log::info("Processing folder ID: $currentFolderId");

            // Get all direct subfolders of the current folder
            $subfolders = Subfolder::where('parent_folder_id', $currentFolderId)->get();

            foreach ($subfolders as $subfolder) {
                Log::info("Queueing subfolder ID for deletion: " . $subfolder->id);

                // Queue sub-subfolders for deletion
                $foldersToDelete[] = $subfolder->id;

                // Delete files within this subfolder
                UsersFolderFile::where('subfolder_id', $subfolder->id)->delete();
                Log::info("Deleted files in subfolder ID: " . $subfolder->id);

                // Delete subfolder directory from storage
                if (Storage::disk('public')->exists($subfolder->folder_path)) {
                    Storage::disk('public')->deleteDirectory($subfolder->folder_path);
                    Log::info("Deleted subfolder directory from storage: " . $subfolder->folder_path);
                }
            }

            // Delete the current subfolder itself
            Subfolder::where('id', $currentFolderId)->delete();
            Log::info("Deleted folder ID: " . $currentFolderId);
        }
    }
}
