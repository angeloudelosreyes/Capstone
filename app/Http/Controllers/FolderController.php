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
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

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
        $directory = 'users/' . auth()->user()->id . '/' . $request->title;

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
            $folderId = Crypt::decryptString($encryptedId);
            Log::info("Decrypted folder ID: $folderId");

            $folder = UsersFolder::findOrFail($folderId);
            Log::info("Folder found: " . $folder->title);

            $folderPath = str_replace('public/', '', $folder->folder_path);
            Log::info("Adjusted folder path for storage deletion: $folderPath");

            $this->deleteSubfoldersAndFilesIteratively($folder->id);
            Log::info("Deleted all subfolders and files for folder ID: $folderId");

            UsersFolderFile::where('users_folder_id', $folder->id)->delete();
            Log::info("Deleted all files in the main folder ID: $folderId");

            $folder->delete();
            Log::info("Main folder deleted: $folderId");

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

        $foldersToDelete = [$parentFolderId];

        while (!empty($foldersToDelete)) {
            $currentFolderId = array_pop($foldersToDelete);
            Log::info("Processing folder ID: $currentFolderId");

            $subfolders = Subfolder::where('parent_folder_id', $currentFolderId)->get();

            foreach ($subfolders as $subfolder) {
                Log::info("Queueing subfolder ID for deletion: " . $subfolder->id);

                $foldersToDelete[] = $subfolder->id;

                UsersFolderFile::where('subfolder_id', $subfolder->id)->delete();
                Log::info("Deleted files in subfolder ID: " . $subfolder->id);

                if ($subfolder->folder_path && Storage::disk('public')->exists($subfolder->folder_path)) {
                    Storage::disk('public')->deleteDirectory($subfolder->folder_path);
                    Log::info("Deleted subfolder directory from storage: " . $subfolder->folder_path);
                }
            }

            Subfolder::where('id', $currentFolderId)->delete();
            Log::info("Deleted folder ID: " . $currentFolderId);
        }
    }


    public function download(Request $request, $encryptedId)
    {
        try {
            // Decrypt the folder ID
            $folderId = Crypt::decryptString($encryptedId);
            $folder = UsersFolder::find($folderId);

            if (!$folder) {
                return back()->with([
                    'message' => 'Folder not found.',
                    'type' => 'error',
                    'title' => 'System Notification'
                ]);
            }

            // Validate password if the folder is password protected
            if ($folder->protected === 'YES') {
                $request->validate([
                    'password' => 'required|string'
                ]);

                // Check if the provided password matches
                if (!Hash::check($request->input('password'), $folder->password)) {
                    return back()->with([
                        'message' => 'Incorrect password.',
                        'type' => 'error',
                        'title' => 'System Notification'
                    ]);
                }
            }

            // Define the path to the folder in storage
            $storagePath = storage_path("app/public/{$folder->folder_path}");

            if (!File::exists($storagePath)) {
                return back()->with([
                    'message' => 'Folder does not exist in storage.',
                    'type' => 'error',
                    'title' => 'System Notification'
                ]);
            }

            // Define the path for the zip file
            $zipFileName = $folder->title . '.zip';
            $zipFilePath = storage_path("app/public/temp/{$zipFileName}");

            // Ensure the temporary directory exists
            if (!File::exists(dirname($zipFilePath))) {
                File::makeDirectory(dirname($zipFilePath), 0755, true);
            }

            // Create the zip file
            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // Add files and folders to the zip file
                $this->addFolderToZip($storagePath, $zip, strlen(dirname($storagePath)) + 1);
                $zip->close();
            } else {
                Log::error("Failed to create zip file at path: {$zipFilePath}");
                return back()->withErrors('Failed to create zip file.');
            }

            // Return the zip file as a download and delete after send
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error occurred during folder download: ' . $e->getMessage());
            return back()->withErrors('An error occurred while processing the download request.');
        }
    }

    /**
     * Recursively add files and folders to the zip archive.
     */
    private function addFolderToZip($folderPath, &$zip, $basePathLength)
    {
        $files = File::allFiles($folderPath);

        foreach ($files as $file) {
            $relativePath = substr($file->getPathname(), $basePathLength);
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $directories = File::directories($folderPath);

        foreach ($directories as $directory) {
            $this->addFolderToZip($directory, $zip, $basePathLength);
        }
    }
}
