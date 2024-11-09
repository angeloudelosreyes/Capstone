<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class SharedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('users_shareable_files')->join('users_folder_files','users_folder_files.id','=','users_shareable_files.users_folder_files_id')->where(['users_shareable_files.recipient_id' => auth()->user()->id])->paginate(18);
        $title = 'Shared With Me';
        return view('shared',compact('title','query'));
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
        // Validate the request inputs
        $request->validate([
            'category' => 'required|string',
            'email' => 'required|email',
            'users_folder_files_id' => 'required|string',
        ]);
    
        // Get the original file ID and decrypt it
        $originalFileId = Crypt::decryptString($request->users_folder_files_id);
        $originalFile = DB::table('users_folder_files')->where('id', $originalFileId)->first();
    
        if (!$originalFile) {
            return back()->with([
                'message' => 'Original file not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    
        // Get the recipient's user ID
        $recipient = DB::table('users')->where('email', $request->email)->first();
    
        if (!$recipient) {
            return back()->with([
                'message' => 'Recipient not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    
        // Create a unique new file name
        $newFileName = uniqid() . '_' . basename($originalFile->file_path);
        
        // Define the user-specific storage path
        $userSpecificPath = storage_path('app/public/users/' . $recipient->id);
    
        // Ensure the directory exists
        if (!File::exists($userSpecificPath)) {
            File::makeDirectory($userSpecificPath, 0755, true);
        }
    
        // Define the new file path in the user's directory
        $newFilePath = 'users/' . $recipient->id . '/' . $newFileName;
    
        // Copy the original file to the new location
        Storage::disk('public')->copy($originalFile->file_path, $newFilePath);
    
        // Get the file extension from the original file
        $fileExtension = pathinfo($originalFile->file_path, PATHINFO_EXTENSION);
    
        // Prepare the new file reference for the database
        $newFileData = [
            'file_path' => $newFilePath,
            'files' => $newFileName, 
            'extension' => $fileExtension, 
            'users_id' => auth()->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    
        // Insert the new file reference and get the new file ID
        $newFileId = DB::table('users_folder_files')->insertGetId($newFileData);
    
        // Determine the recipients based on the category
        $recipients = [];
        if ($request->category == 'Individual') {
            $recipients[] = $recipient; // Already fetched above
        } else {
            $recipients = DB::table('users')
                ->select('name', 'id', 'email')
                ->where(['department' => $request->category])
                ->where('email', '!=', auth()->user()->email)
                ->get();
        }
    
        // Send emails and store shareable file references
        foreach ($recipients as $data) {
            // Send email with the new file link
            Mail::to($data->email)->send(new Notification(Storage::url($newFilePath)));
    
            // Store the new shareable file reference in the database
            DB::table('users_shareable_files')->insert([
                'users_id' => auth()->user()->id,
                'recipient_id' => $data->id,
                'users_folder_files_id' => $newFileId // Store the new file ID
            ]);
        }
    
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
