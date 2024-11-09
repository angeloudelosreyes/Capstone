<?php

namespace App\Http\Controllers;

use App\Models\UsersFolderShareable;
use App\Models\UsersShareableFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsersFolderShareableController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // Create a new shareable folder entry with the user's ID
        $folderShareable = UsersFolderShareable::create([
            'title' => $request->input('title'),
            'users_id' => Auth::id(), // Set the user_id to the ID of the authenticated user
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
            // Create a new shareable folder entry with the authenticated user's ID
            $folderShareable = UsersFolderShareable::create([
                'title' => $request->input('title'),
                'users_id' => $recipient->id, // ID of the authenticated user
                'recipient_id' => $recipient->id, // ID of the recipient
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

    // Update the title of a shared folder
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

        $folderShareable->update(['title' => $newFolderTitle]);

        return back()->with([
            'message' => 'Shared folder title has been updated.',
            'type'    => 'success',
            'title'   => 'System Notification'
        ]);
    }

    // Delete a shared folder and all its associated shareable files
    public function destroy($encryptedId)
    {
        Log::info("Starting destroy function for shared folder ID: $encryptedId");

        DB::beginTransaction();

        try {
            $folderShareableId = Crypt::decryptString($encryptedId);
            Log::info("Decrypted shared folder ID: $folderShareableId");

            $folderShareable = UsersFolderShareable::findOrFail($folderShareableId);
            Log::info("Shared folder found: " . $folderShareable->title);

            // Delete all shareable files in the folder
            $folderShareable->shareableFiles()->delete();
            Log::info("Deleted all shareable files in shared folder ID: $folderShareableId");

            // Delete the shared folder entry
            $folderShareable->delete();
            Log::info("Shared folder deleted: $folderShareableId");

            DB::commit();
            Log::info("Transaction committed successfully for shared folder ID: $folderShareableId");

            return back()->with([
                'message' => 'Shared folder and all associated shareable files have been deleted successfully.',
                'type' => 'success',
                'title' => 'System Notification'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting shared folder and associated items: " . $e->getMessage());

            return back()->with([
                'message' => 'Error deleting shared folder and associated items.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }
}
