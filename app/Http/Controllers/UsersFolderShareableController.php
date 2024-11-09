<?php

namespace App\Http\Controllers;

use App\Models\UsersFolderShareable;
use App\Models\UsersShareableFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // Validate the request to ensure all necessary fields are provided
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'email' => 'required|email',
                'users_folder_id' => 'required|string|nullable', // Folder ID is required for sharing a folder
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
            // Define the storage path for the shared folder based on the recipient ID
            $storagePath = "public/users/{$recipient->id}/shared_folders/{$request->title}";

            // Create the storage directory if it doesn't exist
            if (!Storage::exists($storagePath)) {
                Storage::makeDirectory($storagePath);
                Log::info("Storage directory created at: {$storagePath}");
            }

            // Store the shared folder in the database with a reference to the user's ID
            $folderShareable = UsersFolderShareable::create([
                'title' => $request->input('title'),
                'users_id' => $recipient->id,
                'can_edit' => $request->input('can_edit', false),
                'can_delete' => $request->input('can_delete', false),
            ]);

            Log::info('Shared folder created successfully', ['folder' => $folderShareable]);

            return back()->with([
                'message' => 'Shareable folder created and shared successfully.',
                'type' => 'success',
                'title' => 'System Notification'
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating shared folder', ['error' => $e->getMessage()]);
            return back()->with([
                'message' => 'Error creating shared folder.',
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
        $decryptedId = Crypt::decryptString($id);

        $folderShareable = UsersFolderShareable::with('shareableFiles')->find($decryptedId);

        if (!$folderShareable) {
            return back()->with([
                'message' => 'Shared folder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        $files = $folderShareable->shareableFiles()->paginate(10);

        return view('shared_drive', [
            'title' => $folderShareable->title,
            'files' => $files,
            'folderId' => $id
        ]);
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
