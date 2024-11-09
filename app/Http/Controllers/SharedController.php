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

        // Define sanitized title and storage path
        $title = trim($request->input('title'), '/');
        $storagePath = "public/users/{$recipient->id}/shared_folders/{$title}";

        // Check and create directory if it doesn't exist
        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
            Log::info("Storage directory created at: {$storagePath}");
        }

        // Define the new file path within storage
        $newFilePath = "{$storagePath}/" . basename($originalFile->file_path);

        try {
            // Copy file using absolute paths with the 'public' disk
            Storage::disk('public')->copy($originalFile->file_path, str_replace('public/', '', $newFilePath));
            Log::info('File copied successfully', ['original' => $originalFile->file_path, 'new' => $newFilePath]);
        } catch (\Exception $e) {
            Log::error('Error copying file', ['error' => $e->getMessage()]);
            return back()->with([
                'message' => 'Error occurred while copying the file.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Prepare file reference data for the database
        $newFileData = [
            'file_path' => str_replace("public/", "", $newFilePath), // Remove 'public/' for accessible URL
            'files' => basename($originalFile->file_path),
            'extension' => pathinfo($originalFile->file_path, PATHINFO_EXTENSION),
            'users_id' => $recipient->id, // Save under recipient's ID
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Insert file reference and get new file ID
        $newFileId = DB::table('users_folder_files')->insertGetId($newFileData);
        Log::info('New file reference created', ['newFileId' => $newFileId]);

        // Notify recipient
        Mail::to($recipient->email)->send(new Notification(Storage::url($newFilePath)));
        Log::info('Notification sent to recipient', ['email' => $recipient->email]);

        // Store shareable file reference in the database
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
