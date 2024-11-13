<?php

namespace App\Http\Controllers;

use App\Models\Subfolder;
use App\Models\UsersFolder;
use App\Models\UsersFolderFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use App\Services\DoubleEncryptionService;


class FilesController extends Controller
{

    protected $encryptionService;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();

        // Fetch the user's main folders with subfolders and files
        $folders = UsersFolder::where('users_id', $userId)
            ->with(['subfolders', 'files'])
            ->get();

        $title = 'My Drive';

        // Pass folders to the view
        return view('drive', compact('title', 'folders'));
    }



    /**
     * Show the form for creating a new resource.
     */


    public function __construct(DoubleEncryptionService $encryptionService)

    {

        $this->encryptionService = $encryptionService; // Inject the service

    }


    public function create(Request $request)

    {

        Log::info('Create function called.');


        // Validate incoming request

        $request->validate([

            'fileName' => 'required|string|max:255',

            'fileType' => 'required|string|in:docx',

            'folder_id' => 'required|string',

            'isProtected' => 'nullable|boolean',

            'password' => 'nullable|string|required_if:isProtected,true'

        ]);


        // Decrypt folder_id

        $folderId = Crypt::decryptString($request->input('folder_id'));

        $subfolderId = null;


        // Check if folderId is a subfolder

        $subfolder = DB::table('subfolders')->where('id', $folderId)->first();

        if ($subfolder) {

            $subfolderId = $folderId;

            $folderId = null;

            Log::info("Folder ID belongs to a subfolder. Subfolder ID: $subfolderId");
        } else {

            $folder = DB::table('users_folder')->where('id', $folderId)->first();

            if (!$folder) {

                Log::error("Folder not found for ID: $folderId");

                return response()->json(['error' => 'Folder not found'], 404);
            }

            Log::info("Folder ID belongs to a main folder. Folder ID: $folderId");
        }


        $fileName = $request->input('fileName');

        $fileType = $request->input('fileType');

        $userId = auth()->user()->id;

        $isProtected = $request->input('isProtected', false);

        $password = $isProtected ? $request->input('password') : null;


        Log::info("Attempting to create file: $fileName.$fileType for user ID: $userId");


        // Check for duplicate file name

        $duplicateFileQuery = DB::table('users_folder_files')->where('files', $fileName . '.' . $fileType);

        if ($folderId) {

            $duplicateFileQuery->where('users_folder_id', $folderId);
        } elseif ($subfolderId) {

            $duplicateFileQuery->where('subfolder_id', $subfolderId);
        }

        $duplicateFile = $duplicateFileQuery->exists();


        if ($duplicateFile) {

            Log::warning("Duplicate file name found: $fileName.$fileType in the specified folder or subfolder.");

            return response()->json(['error' => 'File with the same name already exists in this folder or subfolder'], 400);
        }


        // Build the directory path

        $directoryBase = 'users/' . $userId;

        $directory = $folderId ? $this->buildFullPath($folderId, $directoryBase) : $this->buildFullPath($subfolderId, $directoryBase);

        Log::info("Resolved directory path: $directory");


        if (!$directory) {

            Log::error("Folder or subfolder not found for ID: " . ($folderId ?? $subfolderId));

            return response()->json(['error' => 'Folder or subfolder not found'], 404);
        }


        if (!Storage::disk('public')->exists($directory)) {

            Log::info("Creating directory at: $directory");

            Storage::disk('public')->makeDirectory($directory);
        }


        $filePath = $directory . '/' . $fileName . '.' . $fileType;

        Log::info("Resolved file path: $filePath");


        if (!Storage::disk('public')->exists($filePath)) {

            if ($fileType === 'docx') {

                try {

                    $phpWord = new PhpWord();

                    $section = $phpWord->addSection(); // Create a section


                    // Save the document to a temporary file

                    $tempFilePath = tempnam(sys_get_temp_dir(), 'phpword') . '.docx';

                    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

                    $objWriter->save($tempFilePath);


                    // Read the file contents into a variable

                    $fileContents = file_get_contents($tempFilePath);

                    unlink($tempFilePath);

                    Log::info("Temporary file created and deleted.");


                    // Encrypt the file contents if protection is enabled

                    if ($isProtected) {

                        // Use the DoubleEncryptionService to encrypt the file contents

                        $encryptedContents = $this->encryptionService->encrypt($fileContents, $password);

                        Log::info("File contents encrypted.");
                    } else {

                        $encryptedContents = $fileContents;
                    }


                    // Store the (possibly encrypted) file contents

                    Storage::disk('public')->put($filePath, $encryptedContents);

                    Log::info("File created and stored at: $filePath");
                } catch (\Exception $e) {

                    Log::error("Error creating docx file: " . $e->getMessage());

                    return response()->json(['error' => 'Error creating file'], 500);
                }
            }


            $fileSize = Storage::disk('public')->size($filePath);

            Log::info("File size determined: $fileSize bytes");


            // Update the folder_path in the users_folder table

            if ($folderId) {

                Log::info("Updating folder path for folder ID: $folderId to path: $directory");

                $affectedRows = DB::table('users_folder')->where('id', $folderId)->update(['folder_path' => $directory]);

                Log::info("Updated folder path for folder ID: $folderId, Rows affected: $affectedRows");
            }


            // Insert the file record, including the file path

            DB::table('users_folder_files')->insert([

                'users_id' => $userId,

                'users_folder_id' => $folderId,

                'subfolder_id' => $subfolderId,

                'files' => $fileName . '.' . $fileType,

                'size' => $fileSize,

                'extension' => $fileType,

                'protected' => $isProtected ? 'YES' : 'NO',

                'password' => $isProtected ? $this->encryptionService->hashPassword($password) : null,

                'file_path' => $filePath, // Save the file path

                'created_at' => now(),

                'updated_at' => now(),

            ]);


            Log::info("File record inserted into database: $fileName.$fileType");

            return response()->json(['fileName' => $fileName], 201);
        } else {

            Log::warning("File already exists at path: $filePath");

            return response()->json(['error' => 'File already exists'], 400);
        }
    }



    public function store(Request $request)
    {
        Log::info('Store function called.');

        $validator = Validator::make($request->all(), [
            'files.*' => ['required', 'mimes:pdf,docx'],
            'isEncrypted' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string']
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed: ' . json_encode($validator->errors()));
            return back()->withErrors($validator)->withInput();
        }

        $userId = auth()->user()->id;
        $folderId = Crypt::decryptString($request->folder_id);
        $isEncrypted = $request->has('isEncrypted') && $request->isEncrypted;
        $password = $isEncrypted ? $request->password : null;

        Log::info('User  ID: ' . $userId);
        Log::info('Folder ID: ' . $folderId);
        Log::info('Is Encrypted: ' . ($isEncrypted ? 'YES' : 'NO'));

        // Log password information
        if ($isEncrypted) {
            Log::info("Password provided for encryption: " . $password);
            Log::info("Password length: " . strlen($password));
        } else {
            Log::info("Files will not be encrypted.");
        }

        // Base path for the user's files
        $basePath = 'users/' . $userId;
        $directory = $this->buildFullPath($folderId, $basePath);

        if (!$directory) {
            Log::error("Folder ID $folderId not found or is invalid.");
            return back()->with([
                'message' => 'Folder not found. Please check if the folder or subfolder exists.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        Log::info('Final directory path for file storage:', ['directory' => $directory]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $name = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Check for duplicate file name
                $duplicateFileQuery = DB::table('users_folder_files')->where('files', $name);
                if (DB::table('users_folder')->where('id', $folderId)->exists()) {
                    $duplicateFileQuery->where('users_folder_id', $folderId);
                } elseif (DB::table('subfolders')->where('id', $folderId)->exists()) {
                    $duplicateFileQuery->where('subfolder_id', $folderId);
                }
                $duplicateFile = $duplicateFileQuery->exists();

                if ($duplicateFile) {
                    Log::warning("A file with the same name already exists: $name");
                    return back()->with([
                        'message' => 'A file with the same name already exists in this folder.',
                        'type' => 'error',
                        'title' => 'System Notification'
                    ]);
                }

                // Ensure the directory exists
                Storage::disk('public')->makeDirectory($directory);

                // Store the file directly to the desired path
                $path = $directory . '/' . $name;

                // Check if the file is a DOCX and handle accordingly
                if ($extension === 'docx') {
                    // Read the file contents
                    $fileContents = file_get_contents($file);

                    // Encrypt the file contents if protection is enabled
                    if ($isEncrypted) {
                        // Use the encryption service to encrypt the file contents
                        $encryptedContents = $this->encryptionService->encrypt($fileContents, $password);
                        Log::info("File contents encrypted.");
                    } else {
                        $encryptedContents = $fileContents;
                    }

                    // Store the (possibly encrypted) file contents
                    Storage::disk('public')->put($path, $encryptedContents);
                    Log::info("DOCX file uploaded: " . $name);
                } else {
                    // Store the file directly for other types
                    $file->storeAs($directory, $name);
                    Log::info("File uploaded: " . $name);
                }

                // Insert the file record into the database
                DB::table('users_folder_files')->insert([
                    'users_id' => $userId,
                    'users_folder_id' => $folderId,
                    'files' => $name,
                    'extension' => $extension,
                    'protected' => $isEncrypted ? 'YES' : 'NO',
                    'password' => $isEncrypted ? $this->encryptionService->hashPassword($password) : null,
                    'file_path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("File record inserted into database: $name");
            }
        }

        return back()->with([
            'message' => 'Files uploaded successfully.',
            'type' => 'success',
            'title' => 'System Notification'
        ]);
    }
    /**
     * Helper function to recursively build the full path from a subfolder to the root.
     */
    private function buildFullPath($folderId, $basePath)
    {
        // Check if it's a main folder in `users_folder`
        $mainFolder = DB::table('users_folder')->where('id', $folderId)->first();
        if ($mainFolder) {
            // If it's the main folder, just return its path
            return $basePath . '/' . $mainFolder->title;
        }

        // Otherwise, assume it's a subfolder and try to retrieve it
        $subfolder = DB::table('subfolders')->where('id', $folderId)->first();
        if ($subfolder) {
            // Recursively call this function to get the parent path
            $parentPath = $this->buildFullPath($subfolder->parent_folder_id, $basePath);
            return $parentPath . '/' . $subfolder->name; // Append the current folder's name to the parent path
        }

        // If neither a main folder nor a subfolder was found, return null (invalid path)
        return null;
    }



    public function decryptStore(Request $request)
    {
        Log::info('decryptStore function called.');

        $validator = Validator::make($request->all(), [
            'files.*' => ['required']
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed: ' . json_encode($validator->errors()));
            return back()->withErrors($validator)->withInput();
        }

        $id = auth()->user()->id;
        $folder_id = Crypt::decryptString($request->folder_id);
        $title = $request->folder;

        Log::info('User ID: ' . $id);
        Log::info('Folder ID: ' . $folder_id);
        Log::info('Folder Title: ' . $title);

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            foreach ($files as $file) {
                $name = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $path = $file->storeAs("public/users/$id/$title", $name);
                $fileSize = $file->getSize();

                Log::info('File uploaded: ' . $name);
                Log::info('File path: ' . $path);
                Log::info('File size: ' . $fileSize);

                // Ensure the file path is correctly set
                if (Storage::exists($path)) {
                    Log::info('File exists in storage: ' . $path);

                    // Decrypt the file content
                    $encryptedContent = Storage::get($path);
                    if ($encryptedContent === null) {
                        Log::error('Failed to retrieve file content: ' . $path);
                        return back()->with([
                            'message' => 'Failed to retrieve file content.',
                            'type' => 'error',
                            'title' => 'System Notification'
                        ]);
                    }

                    try {
                        $decryptedContent = Crypt::decrypt($encryptedContent);
                    } catch (\Exception $e) {
                        Log::error('Decryption failed: ' . $e->getMessage());
                        return back()->with([
                            'message' => 'Decryption failed. The payload is invalid.',
                            'type' => 'error',
                            'title' => 'System Notification'
                        ]);
                    }

                    // Store the decrypted content back to the file
                    Storage::put($path, $decryptedContent);

                    Log::info('File decrypted and stored: ' . $name);

                    DB::table('users_folder_files')->insert([
                        'users_id' => $id,
                        'users_folder_id' => $folder_id,
                        'files' => $name,
                        'size' => $fileSize,
                        'extension' => $extension
                    ]);
                } else {
                    Log::error('File not found in storage: ' . $path);
                    return back()->with([
                        'message' => 'File not found in storage.',
                        'type' => 'error',
                        'title' => 'System Notification'
                    ]);
                }
            }
            return back()->with([
                'message' => 'New file has been uploaded and decrypted.',
                'type' => 'success',
                'title' => 'System Notification'
            ]);
        } else {
            Log::error('No files uploaded.');
            return back()->with([
                'message' => 'Upload failed.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $decryptedId = Crypt::decryptString($id);

        $folder = UsersFolder::with(['subfolders', 'files'])->find($decryptedId);

        if (!$folder) {
            return back()->with([
                'message' => 'Folder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        dd($folder->subfolders, $folder->files); // Debugging

        return view('drive', [
            'title' => $folder->title,
            'subfolders' => $folder->subfolders,
            'files' => $folder->files,
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
    public function rename(Request $request)
    {
        Log::info('Rename function called.');

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'oldName' => 'required|string',
            'newName' => 'required|string',
            'folder_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed: ' . json_encode($validator->errors()));
            return back()->withErrors($validator)->withInput();
        }

        $userId = auth()->user()->id;
        $folderId = Crypt::decryptString($request->input('folder_id'));

        Log::info('User ID: ' . $userId);
        Log::info('Folder ID: ' . $folderId);
        Log::info('Old File Name: ' . $request->oldName);
        Log::info('New File Name: ' . $request->newName);

        // Build the full directory path using the helper function
        $basePath = "public/users/$userId";
        $directory = $this->buildFullPath($folderId, $basePath);

        if (!$directory) {
            Log::error("Invalid folder or subfolder ID: $folderId.");
            return back()->with([
                'message' => 'Folder or subfolder not found. Please check if they exist.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Construct the old and new file paths
        $oldFilePath = "$directory/{$request->oldName}";
        $newFilePath = "$directory/{$request->newName}";

        // Check if the old file exists
        if (Storage::exists($oldFilePath)) {
            Log::info("File {$request->oldName} found at path: $oldFilePath");

            // Determine if this is a main folder or subfolder
            $isMainFolder = DB::table('users_folder')->where('id', $folderId)->exists();
            $isSubfolder = DB::table('subfolders')->where('id', $folderId)->exists();

            // Check for duplicate file name in the same folder or subfolder
            $duplicateFileQuery = DB::table('users_folder_files')
                ->where('files', $request->newName)
                ->where($isMainFolder ? 'users_folder_id' : 'subfolder_id', $folderId);

            if ($duplicateFileQuery->exists()) {
                Log::error("A file with the name {$request->newName} already exists in this folder.");
                return back()->with([
                    'message' => 'A file with the same name already exists in this folder.',
                    'type' => 'error',
                    'title' => 'System Notification'
                ]);
            }

            // Rename the file in storage
            Storage::move($oldFilePath, $newFilePath);
            Log::info("File renamed from {$request->oldName} to {$request->newName}");

            // Update the filename in the database
            DB::table('users_folder_files')
                ->where([
                    ($isMainFolder ? 'users_folder_id' : 'subfolder_id') => $folderId,
                    'files' => $request->oldName
                ])
                ->update(['files' => $request->newName]);

            return back()->with([
                'message' => 'File has been renamed.',
                'type' => 'success',
                'title' => 'System Notification'
            ]);
        } else {
            Log::error("File {$request->oldName} does not exist in storage.");
            return back()->with([
                'message' => 'File does not exist in storage.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }
    }
    public function showFileDetails($id)
    {
        // Decrypt the file ID
        $fileId = Crypt::decryptString($id);

        // Fetch the file details
        $file = UsersFolderFile::where('id', $fileId)->first();

        if (!$file) {
            // If file not found, return a JSON response with an error message
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Return the file details as a JSON response
        return response()->json(['file' => $file]);
    }
}
