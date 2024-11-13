<?php

namespace App\Http\Controllers;

use App\Mail\Notification;
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
use Illuminate\Support\Facades\Mail;

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

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'email' => 'required|email',
                'users_folder_id' => 'required|string|nullable',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors());
        }

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
            // Generate folder name and create directory
            $folderName = $this->generateFolderName($recipient->id, $request->title);
            $storagePath = "public/users/{$recipient->id}/shared_folders/{$folderName}";

            if (!Storage::exists($storagePath)) {
                Storage::makeDirectory($storagePath);
                Log::info("Storage directory created at: {$storagePath}");
            }

            // Create the shareable folder entry in the database
            $folderShareable = UsersFolderShareable::create([
                'title' => $folderName,
                'users_id' => $recipient->id,
                'can_edit' => $request->input('can_edit', false),
                'can_delete' => $request->input('can_delete', false),
            ]);

            Log::info('Shared folder created successfully', ['folder' => $folderShareable]);

            $originalFolderId = Crypt::decryptString($request->users_folder_id);
            Log::info('Decrypted original folder ID', ['originalFolderId' => $originalFolderId]);

            $originalFolder = DB::table('users_folder')->where('id', $originalFolderId)->first();

            // Recursively copy all files and subfolders
            $this->copyFolderContents($originalFolder, $storagePath, $folderShareable->id, $recipient->id);

            Mail::to($recipient->email)->send(new Notification($recipient->email, 'folder')); // For file
            Log::info('Notification sent to recipient', ['email' => $recipient->email]);

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

    private function copyFolderContents($originalFolder, $destinationPath, $folderShareableId, $recipientId)
    {
        $files = DB::table('users_folder_files')->where('users_folder_id', $originalFolder->id)->get();
        $subfolders = DB::table('subfolders')->where('parent_folder_id', $originalFolder->id)->get();

        // Copy each file
        foreach ($files as $file) {
            $originalFilePath = "public/users/{$file->users_id}/{$originalFolder->title}/{$file->files}";
            $newFileName = $this->generateFileName($folderShareableId, $file->files);
            $newFilePath = "{$destinationPath}/{$newFileName}";

            if (Storage::exists($originalFilePath)) {
                Storage::copy($originalFilePath, $newFilePath);
                Log::info('File copied successfully', ['newFilePath' => $newFilePath]);

                $newFileData = [
                    'file_path' => str_replace("public/", "", $newFilePath),
                    'files' => $newFileName,
                    'extension' => pathinfo($file->file_path, PATHINFO_EXTENSION),
                    'users_id' => $recipientId,
                    'users_folder_shareable_id' => $folderShareableId,
                    'password' => $file->password,
                    'protected' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $newFileId = DB::table('users_folder_files')->insertGetId($newFileData);
                Log::info('New file reference created', ['newFileId' => $newFileId]);

                DB::table('users_shareable_files')->insert([
                    'users_id' => auth()->user()->id,
                    'recipient_id' => $recipientId,
                    'users_folder_files_id' => $newFileId,
                ]);
            } else {
                Log::error('Source file does not exist', ['file' => $originalFilePath]);
            }
        }

        // Copy each subfolder
        foreach ($subfolders as $subfolder) {
            $newSubfolderPath = "{$destinationPath}/{$subfolder->name}";
            Storage::makeDirectory($newSubfolderPath);

            // Recursively copy contents of each subfolder
            $this->copyFolderContents($subfolder, $newSubfolderPath, $folderShareableId, $recipientId);
        }
    }

    private function generateFolderName($userId, $baseName)
    {
        $name = $baseName;
        $counter = 1;

        // Check if the folder name already exists in the database
        while (DB::table('users_folder_shareable')
            ->where('users_id', $userId)
            ->where('title', $name)
            ->exists()
        ) {
            $name = "{$baseName}({$counter})";
            $counter++;
        }

        return $name;
    }

    private function generateFileName($folderId, $fileName)
    {
        $pathInfo = pathinfo($fileName);
        $name = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $counter = 1;

        // Check if the file name already exists in the database for this folder
        while (DB::table('users_folder_files')
            ->where('users_folder_id', $folderId)
            ->where('files', $name . $extension)
            ->exists()
        ) {
            $name = $pathInfo['filename'] . "($counter)";
            $counter++;
        }

        return $name . $extension;
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


    public function show($id)
    {
        // Decrypt the provided encrypted ID
        $decryptedId = Crypt::decryptString($id);
        Log::info("Decrypted ID: {$decryptedId}");

        // Get the current user's ID
        $userId = Auth::id(); // Get the authenticated user's ID

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

            // Construct the path dynamically using the current user's ID and folder title
            $storagePath = "public/users/4/shared_folders/67309c0299570testfolder"; // Use folderModel->title
            Log::info("Storage Path for User Folder: $storagePath");

            // Check if the storage path exists
            if (!Storage::exists($storagePath)) {
                Log::error("Storage path does not exist: $storagePath");
                return redirect()->back()->with([
                    'message' => 'Storage path not found.',
                    'type' => 'error',
                    'title' => 'System Notification'
                ]);
            }

            // Fetch files from storage folder

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

            // Construct the path dynamically using the current user's ID and shareable folder title
            $storagePath = "public/users/{$userId}/shared_folders/" . trim($shareableFolder->title); // Use shareableFolder->title
            Log::info("Storage Path for Shareable Folder: $storagePath");


            // Fetch files from storage folder
            $storageFiles = Storage::files($storagePath);
            Log::info("Fetched files from storage path: " . json_encode($storageFiles));

            // Render view with files data from users_folder_shareable
            return view('sharefolder', [
                'title' => $shareableFolder->title ?? 'Shareable Folder',
                'shareableFolder' => $shareableFolder,
                'files' => $files,
                'storageFiles' => $storageFiles, // Pass storage files to the view
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

            // Check if the directory exists and log if not found
            if (!Storage::exists($storagePath)) {
                Log::warning("Storage directory not found at: {$storagePath}");
            }

            // Retrieve all files associated with this folder
            $files = DB::table('users_folder_files')->where('users_folder_shareable_id', $folderShareableId)->get();

            // Delete each file and its associated database records
            foreach ($files as $file) {
                // Check the specific file path
                $filePath = $storagePath . '/' . $file->files;

                // Check if the file exists and log if not found
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                    Log::info("Deleted file from storage: " . $filePath);
                } else {
                    Log::warning("File not found in storage for deletion: " . $filePath);
                }

                // Delete from the users_shareable_files table
                DB::table('users_shareable_files')->where('users_folder_files_id', $file->id)->delete();
                Log::info("Deleted shareable file record for file ID: " . $file->id);

                // Delete each file entry in the users_folder_files table
                DB::table('users_folder_files')->where('id', $file->id)->delete();
                Log::info("Deleted file record from users_folder_files for file ID: " . $file->id);
            }

            // Attempt to delete the entire directory
            if (Storage::exists($storagePath)) {
                $deleted = Storage::deleteDirectory($storagePath);
                if ($deleted) {
                    Log::info("Storage directory deleted at: {$storagePath}");
                } else {
                    Log::error("Failed to delete storage directory at: {$storagePath}. Check permissions.");
                }
            } else {
                Log::warning("Directory already missing or inaccessible at: {$storagePath}");
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
