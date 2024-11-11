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
use App\Models\UsersShareableFile;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use App\Services\DoubleEncryptionService;

class SharedController extends Controller
{

    protected $encryptionService;

    public function __construct(DoubleEncryptionService $encryptionService)

     {
     
         $this->encryptionService = $encryptionService; // Inject the service
     
     }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();

        // Fetch shareable folders created by the authenticated user
        $createdFolders = UsersFolderShareable::where('users_id', $userId)->paginate(10);

        // Fetch files shared with the authenticated user within the specified shared folders directory
        $sharedFiles = DB::table('users_shareable_files')
            ->join('users_folder_files', 'users_folder_files.id', '=', 'users_shareable_files.users_folder_files_id')
            ->where('users_shareable_files.recipient_id', $userId)
            ->where('users_folder_files.file_path', 'like', "users/{$userId}/shared_folders/%")
            ->whereRaw("CHAR_LENGTH(users_folder_files.file_path) - CHAR_LENGTH(REPLACE(users_folder_files.file_path, '/', '')) = CHAR_LENGTH('users/{$userId}/shared_folders') - CHAR_LENGTH(REPLACE('users/{$userId}/shared_folders', '/', '')) + 1")
            ->paginate(18);

        return view('shared', [
            'title' => 'My Shared Folders & Shared With Me',
            'createdFolders' => $createdFolders,
            'sharedFiles' => $sharedFiles,
        ]);
    }
    public function getSharedFolders()
    {
        // Retrieve the authenticated user's ID
        $userId = auth()->user()->id;

        // Query the shared folders where the user is the owner
        $folders = DB::table('users_folder_shareable')
            ->where('users_id', $userId) // Check if the authenticated user is the owner
            ->select('id', 'title')
            ->get()
            ->map(function ($folder) {
                // Encrypt each folder's ID for secure references
                $folder->encrypted_id = Crypt::encryptString($folder->id);
                return $folder;
            });

        Log::info('Retrieved shared folders for the authenticated user', [$folders]);

        return response()->json(['folders' => $folders]);
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
    $uniqueId = uniqid();
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
    $newFilePath = "{$storagePath}" .$uniqueId. basename($originalFile->file_path);

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
        'files' => $uniqueId . basename($originalFile->file_path),
        'extension' => pathinfo($originalFile->file_path, PATHINFO_EXTENSION),
        'users_id' => $recipient->id, // Save under recipient's ID
        'protected' => $originalFile->protected, // Add the protected field
        'password' => $originalFile->password, // Add the hashed password field
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
        Log::info("Starting edit function for shareable file ID: $id");

        try {
            // Decrypt the ID
            $decryptedId = Crypt::decryptString($id);

            // Find the record in users_shareable_files based on users_folder_files_id
            $shareableFile = DB::table('users_shareable_files')->where('users_folder_files_id', $decryptedId)->first();

            if ($shareableFile) {
                Log::info("Shareable file found with users_folder_files_id: $decryptedId");

                // Get the file record from users_folder_files table
                $query = DB::table('users_folder_files')->where('id', $decryptedId)->first();
                if ($query) {
                    $filePath = "public/{$query->file_path}";
                    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

                    // Initialize the content to display
                    $content = '';

                    // If the file is a .docx file, read its content
                    if ($extension === 'docx') {
                        if (Storage::exists($filePath)) {
                            try {
                                $phpWord = \PhpOffice\PhpWord\IOFactory::load(storage_path('app/' . $filePath));
                                $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
                                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
                                $writer->save($tempFile);
                                $content = file_get_contents($tempFile);
                                unlink($tempFile);
                            } catch (\Exception $e) {
                                return redirect()->back()->with('error', 'Error loading .docx file: ' . $e->getMessage());
                            }
                        } else {
                            Log::warning("File does not exist in storage at path: $filePath");
                            return redirect()->back()->with('error', 'File not found in storage.');
                        }
                    }

                    // Get folder title for display
                    $folderTitle = $this->getFolderTitle($query);

                    // Return the edit view with the compacted variables
                    return view('edit', compact('query', 'content', 'extension', 'folderTitle'));
                } else {
                    throw new \Exception("File record not found in users_folder_files for ID: $decryptedId");
                }
            } else {
                throw new \Exception("No shareable file found with users_folder_files_id: $decryptedId");
            }
        } catch (\Exception $e) {
            Log::error("Error loading shareable file for editing: " . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Error loading shareable file.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }

    private function getFolderTitle($query)
    {
        $folder = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first();
        if (!$folder) {
            $folder = DB::table('subfolders')->where(['id' => $query->subfolder_id])->first();
            return $folder ? $folder->name : 'Unknown Folder';
        }
        return $folder->title;
    }

    public function paste(Request $request, string $destinationFolderId)
    {
        Log::info("Received request to paste file into shared folder with ID: " . $destinationFolderId);

        try {
            // Decrypt the destination folder ID
            $decryptedDestinationFolderId = Crypt::decryptString($destinationFolderId);
            $destinationFolder = DB::table('users_folder_shareable')->where('id', $decryptedDestinationFolderId)->first();

            if (!$destinationFolder) {
                Log::error("Destination shared folder not found: " . $decryptedDestinationFolderId);
                return back()->with(['error' => 'Destination shared folder not found.']);
            }

            // Retrieve the file ID from the request and fetch the file from the database
            $fileId = $request->input('fileId');
            $decryptedFileId = Crypt::decryptString($fileId);
            $copiedFile = DB::table('users_folder_files')->where('id', $decryptedFileId)->first();

            if (!$copiedFile) {
                Log::error("File to paste not found: " . $decryptedFileId);
                return back()->with(['error' => 'File not found.']);
            }

            Log::info("Pasting file: ", ['copiedFile' => $copiedFile]);

            // Build the source path
            $sourcePath = "public/{$copiedFile->file_path}";

            // Generate a unique file name for the copied file
            $newFileName = uniqid() . '_' . basename($copiedFile->files);

            // Build the destination path for the shared folder
            $destinationPath = "public/users/{$copiedFile->users_id}/shared_folders/{$destinationFolder->title}/$newFileName";

            Log::info("Source path: " . $sourcePath);
            Log::info("Destination path: " . $destinationPath);

            // Copy the file in storage
            if (Storage::exists($sourcePath)) {
                Storage::copy($sourcePath, $destinationPath);
                Log::info("File copied from " . $sourcePath . " to " . $destinationPath);

                // Insert a new record for the copied file
                DB::table('users_folder_files')->insert([
                    'users_id' => $copiedFile->users_id,
                    'users_folder_shareable_id' => $decryptedDestinationFolderId, // Set to the shared folder ID
                    'file_path' => str_replace('public/', '', $destinationPath),
                    'files' => $newFileName,
                    'size' => $copiedFile->size,
                    'extension' => $copiedFile->extension,
                    'protected' => $copiedFile->protected,
                    'password' => $copiedFile->password,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("File pasted successfully in shared folder.", ['newFileName' => $newFileName]);
                return back()->with(['message' => 'File pasted successfully in shared folder.', 'type' => 'success', 'title' => 'Success']);
            } else {
                Log::error("Source file not found in storage: " . $sourcePath);
                return back()->with(['error' => 'Source file not found in storage.']);
            }
        } catch (\Exception $e) {
            Log::error("Error occurred while pasting file in shared folder: " . $e->getMessage());
            return back()->with(['error' => 'An error occurred while processing the request.']);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        Log::info("Starting update function for shareable file ID: $id");

        $request->validate([
            'new_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // Decrypt the ID
            $decryptedId = Crypt::decryptString($id);

            // Find the record in users_shareable_files based on users_folder_files_id
            $shareableFile = DB::table('users_shareable_files')->where('users_folder_files_id', $decryptedId)->first();

            if ($shareableFile) {
                Log::info("Shareable file found with users_folder_files_id: $decryptedId");

                // Get the file record from users_folder_files table
                $fileRecord = DB::table('users_folder_files')->where('id', $decryptedId)->first();
                if ($fileRecord) {
                    $oldFilePath = "public/{$fileRecord->file_path}";
                    $extension = pathinfo($oldFilePath, PATHINFO_EXTENSION); // Preserve file extension

                    // Check if the new name already includes the extension, and add it only if it’s missing
                    $newName = $request->input('new_name');
                    if (pathinfo($newName, PATHINFO_EXTENSION) !== $extension) {
                        $newFileName = "{$newName}.{$extension}";
                    } else {
                        $newFileName = $newName;
                    }

                    $newFilePath = "public/users/{$fileRecord->users_id}/shared_folders/{$newFileName}";

                    // Check if the file exists in storage and rename it
                    if (Storage::exists($oldFilePath)) {
                        Storage::move($oldFilePath, $newFilePath);
                        Log::info("Storage file renamed from: $oldFilePath to: $newFilePath");
                    } else {
                        Log::warning("File does not exist in storage at path: $oldFilePath");
                        throw new \Exception("File not found in storage.");
                    }

                    // Update the file reference in the database
                    DB::table('users_folder_files')->where('id', $decryptedId)->update([
                        'file_path' => str_replace('public/', '', $newFilePath),
                        'files' => $newFileName,
                        'updated_at' => now(),
                    ]);
                    Log::info("File reference updated in users_folder_files for ID: $decryptedId");

                    DB::commit();
                    Log::info("Transaction committed successfully for shareable file update with users_folder_files_id: $decryptedId");

                    return back()->with([
                        'message' => 'File has been updated successfully.',
                        'type' => 'success',
                        'title' => 'System Notification'
                    ]);
                } else {
                    throw new \Exception("File record not found in users_folder_files for ID: $decryptedId");
                }
            } else {
                throw new \Exception("No shareable file found with users_folder_files_id: $decryptedId");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating shareable file: " . $e->getMessage());

            return back()->with([
                'message' => 'Error updating shareable file.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Log::info("Starting destroy function for shareable file ID: $id");

        DB::beginTransaction();

        try {
            // Decrypt the ID
            $decryptedId = Crypt::decryptString($id);

            // Find the record in users_shareable_files based on users_folder_files_id
            $shareableFile = DB::table('users_shareable_files')->where('users_folder_files_id', $decryptedId)->first();

            if ($shareableFile) {
                Log::info("Shareable file found with users_folder_files_id: $decryptedId, deleting...");

                // Get the file path from users_folder_files table
                $fileRecord = DB::table('users_folder_files')->where('id', $decryptedId)->first();
                if ($fileRecord) {
                    $filePath = "public/{$fileRecord->file_path}";

                    // Delete the file from storage
                    if (Storage::exists($filePath)) {
                        Storage::delete($filePath);
                        Log::info("Storage file deleted at: $filePath");
                    }

                    // Delete the file reference from users_folder_files table
                    DB::table('users_folder_files')->where('id', $decryptedId)->delete();
                    Log::info("File reference deleted from users_folder_files for ID: $decryptedId");
                }

                // Delete the shareable file record from users_shareable_files table
                DB::table('users_shareable_files')->where('users_folder_files_id', $decryptedId)->delete();
                Log::info("Shareable file reference deleted from users_shareable_files for users_folder_files_id: $decryptedId");

                DB::commit();
                Log::info("Transaction committed successfully for shareable file with users_folder_files_id: $decryptedId");

                return back()->with([
                    'message' => 'Shareable file has been deleted successfully.',
                    'type' => 'success',
                    'title' => 'System Notification'
                ]);
            } else {
                throw new \Exception("No shareable file found with users_folder_files_id: $decryptedId");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting shareable file: " . $e->getMessage());

            return back()->with([
                'message' => 'Error deleting shareable file.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }
    public function download(Request $request, $id)
    {
        // Validate the password input
        $request->validate([
            'password' => 'required|string'
        ]);

        $password = $request->input('password');
        Log::info('Password entered by user: ' . $password);

        try {
            // Decrypt the provided file ID
            $decryptedId = Crypt::decryptString($id);
            Log::info('Decrypted ID: ' . $decryptedId);

            // Retrieve the file details
            $query = DB::table('users_folder_files')->where('id', $decryptedId)->first();
            if (!$query) {
                Log::error('File not found in users_folder_files table.');
                return response()->json(['error' => 'File not found.'], 404);
            }

            Log::info('File query result: ' . json_encode($query));

            // If there's no folder ID, use the file path directly
            $filePath = "public/{$query->file_path}";

            Log::info('File path: ' . $filePath);

            // Check if the file exists in storage
            if (Storage::exists($filePath)) {
                Log::info('File exists in storage.');

                $fileContent = Storage::get($filePath);
                $encryptedContent = Crypt::encrypt($fileContent);

                $storagePath = storage_path('app/protected');
                if (!File::exists($storagePath)) {
                    File::makeDirectory($storagePath, 0755, true);
                }

                // Create a password-protected ZIP file
                $zip = new ZipArchive();
                $zipFileName = $storagePath . '/protected-file.zip';

                if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    $zip->addFromString($query->files, $encryptedContent);
                    $zip->setEncryptionName($query->files, ZipArchive::EM_AES_128, $password);
                    $zip->close();

                    // Return the zip file for download
                    return response()->download($zipFileName)->deleteFileAfterSend(true);
                } else {
                    Log::error('Failed to open the ZIP file.');
                    return response()->json(['error' => 'Failed to create the zip file.'], 500);
                }
            } else {
                Log::error('File not found in storage.');
                return response()->json(['error' => 'File not found.'], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error occurred: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the request.'], 500);
        }
    }



    public function move(Request $request, string $fileId, string $destinationFolderId)
    {
        Log::info("Received encrypted fileId: " . $fileId);
        Log::info("Received encrypted destinationFolderId: " . $destinationFolderId);

        try {
            // Decrypt the IDs
            $decryptedFileId = Crypt::decryptString($fileId);
            $decryptedDestinationFolderId = Crypt::decryptString($destinationFolderId);
            Log::info("Decrypted fileId: " . $decryptedFileId);
            Log::info("Decrypted destinationFolderId: " . $decryptedDestinationFolderId);
        } catch (\Exception $e) {
            Log::error("Decryption failed: " . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Invalid encrypted IDs. Please try again.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }

        // Retrieve the file from the users_folder_files table
        $file = DB::table('users_folder_files')->where('id', $decryptedFileId)->first();

        // Retrieve the destination folder from the users_folder_shareable table
        $destinationFolder = DB::table('users_folder_shareable')->where('id', $decryptedDestinationFolderId)->first();

        // Check if both file and destination folder exist
        if (!$file || !$destinationFolder) {
            Log::error('File or destination folder not found.');
            return redirect()->back()->with([
                'message' => 'File or destination folder not found.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }
        Log::info('File and destination folder found, proceeding with database update.');

        // Build the destination path
        $destinationPath = "public/users/{$file->users_id}/shared_folders/{$destinationFolder->title}/{$file->files}";

        // Update the file's folder reference in the database to the new destination folder
        DB::table('users_folder_files')->where('id', $decryptedFileId)->update([
            'users_folder_id' => null, // Set to null since we're moving it to a shared folder
            'users_folder_shareable_id' => $decryptedDestinationFolderId, // Set the new shared folder ID
            'file_path' => str_replace('public/', '', $destinationPath), // Update the file path to reflect the new location
        ]);

        Log::info("Database updated for file ID $decryptedFileId with new shared folder ID $decryptedDestinationFolderId");

        // Define the source path
        $sourcePath = "public/{$file->file_path}";

        // Check if the source file exists
        if (Storage::exists($sourcePath)) {
            // Move the file in storage
            Storage::move($sourcePath, $destinationPath);
            Log::info("File moved in storage from $sourcePath to $destinationPath");

            return redirect()->back()->with([
                'message' => 'File moved successfully.',
                'type' => 'success',
                'title' => 'Success'
            ]);
        } else {
            Log::error("File not found in storage: $sourcePath");
            return redirect()->back()->with([
                'message' => 'File not found in storage.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }
    }


    private function buildFullPath($folderId, $basePath)
    {
        // Check if it's a main folder in users_folder
        $mainFolder = DB::table('users_folder')->where('id', $folderId)->first();
        if ($mainFolder) {
            // If it's the main folder, just return its path
            Log::info("Found main folder: " . $mainFolder->title);
            return $basePath . '/' . $mainFolder->title;
        }

        // Otherwise, assume it's a subfolder and try to retrieve it
        $subfolder = DB::table('subfolders')->where('id', $folderId)->first();
        if ($subfolder) {
            // Recursively call this function to get the parent path
            $parentPath = $this->buildFullPath($subfolder->parent_folder_id, $basePath);
            Log::info("Found subfolder: " . $subfolder->name . " with parent path: " . $parentPath);
            return $parentPath . '/' . $subfolder->name; // Append the current folder's name to the parent path
        }

        // If neither a main folder nor a subfolder was found, return null (invalid path)
        Log::error("No folder found for ID: " . $folderId);
        return null;
    }
}
