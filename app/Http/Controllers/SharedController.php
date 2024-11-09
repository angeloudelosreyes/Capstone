<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\Notification;
use App\Models\UsersFolderShareable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\UsersFolder;
use Illuminate\Support\Facades\Log;


class SharedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();

        // Fetch shareable folders created by the authenticated user
        $createdFolders = UsersFolderShareable::where('users_id', $userId)->paginate(10);

        // Fetch files shared with the authenticated user
        $sharedFiles = DB::table('users_shareable_files')
            ->join('users_folder_files', 'users_folder_files.id', '=', 'users_shareable_files.users_folder_files_id')
            ->where('users_shareable_files.recipient_id', $userId)
            ->paginate(18);

        return view('shared', [
            'title' => 'My Shared Folders & Shared With Me',
            'createdFolders' => $createdFolders,
            'sharedFiles' => $sharedFiles,
        ]);
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

        Log::info('File/Folder sharing request received', ['request' => $request->all()]);


        try {

            // Validate the request to ensure either a file or folder ID is provided

            $request->validate([

                'category' => 'required|string',

                'email' => 'required|email',

                'users_folder_files_id' => 'required_without:users_folder_id|string|nullable', // Required if users_folder_id is not present

                'users_folder_id' => 'required_without:users_folder_files_id|string|nullable', // Required if users_folder_files_id is not present

            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::error('Validation failed', ['errors' => $e->errors()]);

            return back()->withErrors($e->errors());
        }


        // Find the recipient by email

        $recipient = DB::table('users')->where('email', $request->email)->first();

        Log::info('Recipient found', ['recipient' => $recipient]);


        if (!$recipient) {

            Log::warning('Recipient not found', ['email' => $request->email]);

            return back()->with([

                'message' => 'Recipient not found.',

                'type' => 'error',

                'title' => 'System Notification'

            ]);
        }


        // Check if sharing a file or a folder

        if ($request->users_folder_files_id) {

            // Sharing a file

            return $this->shareFile($request, $recipient);
        } elseif ($request->users_folder_id) {

            // Sharing a folder

            return $this->shareFolder($request, $recipient);
        }


        Log::warning('No valid sharing option selected');

        return back()->with([

            'message' => 'No valid sharing option selected.',

            'type' => 'error',

            'title' => 'System Notification'

        ]);
    }

    private function shareFile(Request $request, $recipient)
    {
        $originalFileId = Crypt::decryptString($request->users_folder_files_id);
        $originalFile = DB::table('users_folder_files')->where('id', $originalFileId)->first();

        Log::info('Original file retrieved', ['originalFileId' => $originalFileId, 'originalFile' => $originalFile]);

        if (!$originalFile) {
            Log::warning('Original file not found', ['originalFileId' => $originalFileId]);
            return back()->with([
                'message' => 'Original file not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Create a unique new file name
        $newFileName = uniqid() . '_' . basename($originalFile->file_path);
        $newFilePath = 'users/' . $recipient->id . '/' . $newFileName;

        // Copy the original file to the new location
        try {
            // Ensure the directory exists
            Storage::disk('public')->makeDirectory('users/' . $recipient->id);
            Storage::disk('public')->copy($originalFile->file_path, $newFilePath);
            Log::info('File copied successfully', ['original' => $originalFile->file_path, 'new' => $newFilePath]);
        } catch (\Exception $e) {
            Log::error('Error copying file', ['error' => $e->getMessage()]);
            return back()->with([
                'message' => 'Error occurred while copying the file.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Prepare the new file reference for the database
        $newFileData = [
            'file_path' => $newFilePath,
            'files' => $newFileName,
            'extension' => pathinfo($originalFile->file_path, PATHINFO_EXTENSION),
            'users_id' => $recipient->id, // Save under recipient's ID
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Insert the new file reference and get the new file ID
        $newFileId = DB::table('users_folder_files')->insertGetId($newFileData);
        Log::info('New file reference created', ['newFileId' => $newFileId]);

        // Notify recipient
        Mail::to($recipient->email)->send(new Notification(Storage::url($newFilePath)));
        Log::info('Notification sent to recipient', ['email' => $recipient->email]);

        // Store the new shareable file reference in the database
        DB::table('users_shareable_files')->insert([
            'users_id' => auth()->user()->id,
            'recipient_id' => $recipient->id,
            'users_folder_files_id' => $newFileId // Store the new file ID
        ]);
        Log::info('Shareable file reference stored', ['newFileId' => $newFileId]);

        return back()->with([
            'message' => 'Your selected file has been shared.',
            'type' => 'success',
            'title' => 'System Notification'
        ]);
    }

    private function shareFolder(Request $request, $recipient)
    {
        $originalFolderId = Crypt::decryptString($request->users_folder_id);
        $originalFolder = UsersFolder::find($originalFolderId);

        Log::info('Original folder retrieved', ['originalFolderId' => $originalFolderId, 'originalFolder' => $originalFolder]);

        if (!$originalFolder) {
            Log::warning('Original folder not found', ['originalFolderId' => $originalFolderId]);
            return back()->with([
                'message' => 'Original folder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Create a new folder for the recipient
        $newFolder = new UsersFolder();
        $newFolder->users_id = $recipient->id;

        // Set the new folder name with a unique ID and original folder title
        $newFolder->title = uniqid() . '_' . $originalFolder->title; // Ensure the folder name is unique and descriptive
        $newFolder->file_path = 'users/' . $recipient->id . '/' . $newFolder->title; // Set the file path for the new folder

        // Save the new folder
        $newFolder->save();

        Log::info('New folder created', ['newFolderId' => $newFolder->id]);

        // Copy files from the original folder to the new folder
        $files = DB::table('users_folder_files')->where('users_folder_id', $originalFolderId)->get(); // Get all files in the original folder

        foreach ($files as $file) {
            $newFileName = uniqid() . '_' . basename($file->file_path);
            $newFilePath = 'users/' . $recipient->id . '/' . $newFolder->title . '/' . $newFileName; // Set the file path within the new folder

            // Ensure the directory exists
            Storage::disk('public')->makeDirectory('users/' . $recipient->id . '/' . $newFolder->title);

            // Copy the file to the new location
            try {
                Storage::disk('public')->copy($file->file_path, $newFilePath);
                Log::info('File copied to new folder', ['original' => $file->file_path, 'new' => $newFilePath]);
            } catch (\Exception $e) {
                Log::error('Error copying file to new folder', ['error' => $e->getMessage()]);
                return back()->with([
                    'message' => 'Error occurred while copying files to the folder.',
                    'type' => 'error',
                    'title' => 'System Notification'
                ]);
            }

            // Prepare the new file reference for the database
            DB::table('users_folder_files')->insert([
                'users_id' => $recipient->id,
                'files' => $newFileName,
                'extension' => pathinfo($file->file_path, PATHINFO_EXTENSION),
                'file_path' => $newFilePath,
                'users_folder_id' => $newFolder->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('New file reference created in folder', ['newFileName' => $newFileName]);
        }

        // Optionally notify the recipient via email
        Mail::to($recipient->email)->send(new Notification("You have received a new folder: " . $originalFolder->title));
        Log::info('Folder notification sent to recipient', ['email' => $recipient->email]);

        return back()->with([
            'message' => 'The folder has been shared successfully.',
            'type' => 'success',
            'title' => 'System Notification'
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
