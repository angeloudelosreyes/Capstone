<?php

namespace App\Http\Controllers;

use App\Models\UsersFolder;
use App\Models\UsersFolderShareable;
use App\Models\UsersShareableFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\File;


class UsersFolderShareableController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // Define the storage path for the new shareable folder based on the user's ID
        $userId = Auth::id();
        $storagePath = "public/users/{$userId}/shared_folders/{$request->input('title')}";

        // Create the storage directory if it doesn't exist
        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
            Log::info("Storage directory created at: {$storagePath}");
        }

        // Create a new shareable folder entry in the database
        $folderShareable = UsersFolderShareable::create([
            'title' => $request->input('title'),
            'users_id' => $userId, // Set the user_id to the ID of the authenticated user
            'can_edit' => false,
            'can_delete' => false,
        ]);

        return back()->with([
            'message' => 'Shareable folder created successfully.',
            'type' => 'success',
            'title' => 'System Notification'
        ]);
    }

    public function createSharedFolder(Request $request)
{
    Log::info('Folder sharing request received', ['request' => $request->all()]);

    // Validate the request
    try {
        $request->validate([
            'title' => 'required|string|max:255',
            'email' => 'required|email',
            'users_folder_id' => 'required|string|nullable', // Ensure this is present
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed', ['errors' => $e->errors()]);
        return back()->withErrors($e->errors());
    }

    // Find the recipient by email
    $recipient = DB::table('users')->where('email', $request->email)->first();

    if (!$recipient) {
        Log::warning('Recipient not found', ['email' => $request->email]);
        return back()->with([
            'message' => 'Recipient not found.',
            'type' => 'error',
            'title' => 'System Notification'
        ]);
    }

    try {
        $uniqueId = uniqid();
        $storagePath = "public/users/{$recipient->id}/shared_folders/$uniqueId{$request->title}";

        // Create the storage directory if it doesn't exist
        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
            Log::info("Storage directory created at: {$storagePath}");
        }

        // Store the shared folder in the database
        $folderShareable = UsersFolderShareable::create([
            'title' => uniqid() . '_' . $request->input('title'),
            'users_id' => $recipient->id,
            'can_edit' => $request->input('can_edit', false),
            'can_delete' => $request->input('can_delete', false),
        ]);

        Log::info('Shared folder created successfully', ['folder' => $folderShareable]);

        // Decrypt the original folder ID
        $originalFolderId = Crypt::decryptString($request->users_folder_id); // Decrypt the folder ID

        // Log the decrypted folder ID for debugging
        Log::info('Decrypted original folder ID being queried', ['originalFolderId' => $originalFolderId]);

        // Retrieve the folder title and files from the original folder
        $originalFolder = DB::table('users_folder')->where('id', $originalFolderId)->first();
        $files = DB::table('users_folder_files')->where('users_folder_id', $originalFolderId)->get();

        // Log the retrieved files
        Log::info('Retrieved files from original folder', ['files' => $files]);

        if ($files->isEmpty()) {
            Log::warning('No files found for the specified folder ID', ['originalFolderId' => $originalFolderId]);
            return back()->with([
                'message' => 'No files found in the specified folder.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Proceed to copy files if there are any
        foreach ($files as $file) {
            // Define the original file path using the folder title dynamically
            $originalFilePath = "public/users/{$file->users_id}/{$originalFolder->title}/{$file->files}";

            // Define the new file path
            $newFileName = uniqid() . '_' . basename($originalFilePath); // Generate a new file name
            $newFilePath = "$storagePath/$newFileName"; // Adjust based on your structure
        
            // Log the paths
            Log::info('Attempting to copy file', [
                'original' => $originalFilePath,
                'new' => $newFilePath,
            ]);
        
            try {
                // Check if the original file exists
                if (Storage::exists($originalFilePath)) {
                    Log::info('Source file exists', ['file' => $originalFilePath]);
        
                    // Attempt to copy the file
                    if (Storage::copy($originalFilePath, $newFilePath)) {
                        Log::info('File copied successfully', ['newFilePath' => $newFilePath]);
                    } else {
                        Log::error('Failed to copy file', ['original' => $originalFilePath, 'new' => $newFilePath]);
                    }
                } else {
                    Log::error('Source file does not exist', ['file' => $originalFilePath]);
                }
            } catch (\Exception $e) {
                Log::error('Error during file copy', [
                    'error' => $e->getMessage(),
                    'original' => $originalFilePath,
                    'new' => $newFilePath,
                ]);
            }
        }

        return back()->with([
            'message' => 'Shared folder created successfully with files.',
            'type' => 'success',
            'title' => 'System Notification'
        ]);
    } catch (\Exception $e) {
        Log::error('Error creating shared folder', ['error' => $e->getMessage()]);
        return back()->with([
            'message' => 'An error occurred while creating the shared folder.',
            'type' => 'error',
            'title' => 'System Notification'
        ]);
    }
}
    // Method to add shareable files to a specific shared folder
    public function addShareableFile(Request $request, $folderShareableId)
    {
        $request->validate([
            'users_id' => 'required|exists:users,id',
            'recipient_id' => 'required|exists:users,id',
            'users_folder_files_id' => 'required|exists:users_folder_files,id',
        ]);

        $folderShareable = UsersFolderShareable::findOrFail($folderShareableId);

        $shareableFile = UsersShareableFile::create([
            'users_id' => $request->input('users_id'),
            'recipient_id' => $request->input('recipient_id'),
            'users_folder_files_id' => $request->input('users_folder_files_id'),
        ]);

        $folderShareable->shareableFiles()->save($shareableFile);

        return response()->json([
            'message' => 'Shareable file added to the shared folder successfully',
            'data' => $shareableFile,
        ], 201);
    }

    // Method to view all shareable folders and files for the authenticated user
    public function viewSharedFolders()
    {
        $userId = Auth::id();

        // Fetch shareable folders and shared files for the user
        $sharedFolders = UsersFolderShareable::where('shared_with_user_id', $userId)->get();
        $sharedFiles = UsersShareableFile::with('sharedFile')
            ->where('recipient_id', $userId)
            ->paginate(10);

        return view('shared', [
            'title' => 'Shared With Me',
            'sharedFolders' => $sharedFolders,
            'sharedFiles' => $sharedFiles,
        ]);
    }


    // Show a specific shared folder
    public function show($id)
    {
        // Decrypt the provided encrypted ID
        $decryptedId = Crypt::decryptString($id);
        Log::info("Decrypted ID: {$decryptedId}");

        // Try to find the folder in the users_folder_shareable table
        $shareableFolder = DB::table('users_folder_shareable')->where('id', $decryptedId)->first();

        // If the folder is not found in users_folder_shareable, look in users_folder
        if (!$shareableFolder) {
            Log::warning("No shareable folder found with ID: {$decryptedId} in users_folder_shareable.");

            // Check in the users_folder table
            $folderModel = UsersFolder::find($decryptedId);

            if (!$folderModel) {
                // Return error if the folder is not found in both tables
                return redirect()->back()->with([
                    'message' => 'Folder not found.',
                    'type' => 'error',
                    'title' => 'System Notification'
                ]);
            }

            // Fetch only files directly under this folder
            $files = DB::table('users_folder_files')
                ->where('users_folder_id', $decryptedId)
                ->whereNull('subfolder_id') // Exclude files in subfolders
                ->paginate(10);

            // Render view with files data from users_folder
            return view('sharefolder', [
                'title' => $folderModel->title ?? 'Folder',
                'shareableFolder' => $folderModel,
                'files' => $files,
                'folderId' => $id
            ]);
        } else {
            Log::info("Found shareable folder with ID: {$shareableFolder->id} in users_folder_shareable.");

            // Fetch only files directly under this shareable folder
            $files = DB::table('users_folder_files')
                ->where('users_folder_shareable_id', $decryptedId)
                ->whereNull('subfolder_id') // Exclude files in subfolders
                ->paginate(10);

            // Render view with files data from users_folder_shareable
            return view('sharefolder', [
                'title' => $shareableFolder->title ?? 'Shareable Folder',
                'shareableFolder' => $shareableFolder,
                'files' => $files,
                'folderId' => $id
            ]);
        }
    }



    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'new' => 'required|string|max:255',
        ]);

        $decryptedId = Crypt::decryptString($request->id);
        $folderShareable = UsersFolderShareable::findOrFail($decryptedId);

        $oldFolderTitle = $folderShareable->title;
        $newFolderTitle = $request->input('new');

        // Define the old and new storage paths
        $userId = Auth::id();
        $oldStoragePath = "public/users/{$userId}/shared_folders/{$oldFolderTitle}";
        $newStoragePath = "public/users/{$userId}/shared_folders/{$newFolderTitle}";

        // Rename the directory in storage
        if (Storage::exists($oldStoragePath)) {
            Storage::move($oldStoragePath, $newStoragePath);
            Log::info("Storage directory renamed from: {$oldStoragePath} to {$newStoragePath}");
        }

        // Update the title in the database
        $folderShareable->update(['title' => $newFolderTitle]);

        return back()->with([
            'message' => 'Shared folder title has been updated.',
            'type'    => 'success',
            'title'   => 'System Notification'
        ]);
    }

    public function destroy($encryptedId)
    {
        Log::info("Starting destroy function for shareable folder ID: $encryptedId");

        DB::beginTransaction();

        try {
            $folderShareableId = Crypt::decryptString($encryptedId);
            Log::info("Decrypted shareable folder ID: $folderShareableId");

            $folderShareable = UsersFolderShareable::findOrFail($folderShareableId);
            Log::info("Shareable folder found: " . $folderShareable->title);

            // Define the storage path based on the user's ID and folder title
            $userId = Auth::id();
            $storagePath = "public/users/{$userId}/shared_folders/{$folderShareable->title}";

            // Delete the directory and all files within it from storage
            if (Storage::exists($storagePath)) {
                Storage::deleteDirectory($storagePath);
                Log::info("Storage directory deleted at: {$storagePath}");
            }

            // Delete the shareable folder entry from the database
            $folderShareable->delete();
            Log::info("Shareable folder deleted from database: $folderShareableId");

            DB::commit();
            Log::info("Transaction committed successfully for shareable folder ID: $folderShareableId");

            return back()->with([
                'message' => 'Shareable folder and all associated files have been deleted successfully.',
                'type' => 'success',
                'title' => 'System Notification'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting shareable folder and associated items: " . $e->getMessage());

            return back()->with([
                'message' => 'Error deleting shareable folder and associated items.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }
}
