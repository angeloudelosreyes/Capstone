<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\File;

class DriveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getFolders()
    {
        $folders = DB::table('users_folder')
            ->where('users_id', auth()->user()->id)
            ->select('id', 'title')
            ->get()
            ->map(function ($folder) {
                $folder->encrypted_id = Crypt::encryptString($folder->id);
                return $folder;
            });

        return response()->json(['folders' => $folders]);
    }


    public function index()
    {
        $query = DB::table('users_folder_files')
            ->where('users_id', auth()->user()->id)
            ->whereNull('users_folder_shareable_id') // Exclude records with a non-null users_folder_shareable_id
            ->paginate(18);

        $title = 'My Drive';
        return view('mydrive', compact('title', 'query'));
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
        //
    }

    public function sharedShow(Request $request, string $id)

    {

        // Decrypt the file ID

        $fileId = Crypt::decryptString($id);


        // Query for the file details in the database

        $query = DB::table('users_folder_files')->where('id', $fileId)->first();


        if (!$query) {

            return redirect()->back()->with('error', 'File not found.');
        }


        // Build the user-specific path

        $userId = $query->users_id; // Get the user ID from the file record

        $filePath = ltrim($query->file_path, '/'); // Ensure no leading slash


        // Log the file path

        Log::info('File path from database: ' . $filePath);


        // Check if the file exists in the storage

        if (!Storage::disk('public')->exists($filePath)) {

            $fullStoragePath = storage_path('app/public/' . $filePath);

            Log::error('File not found at path: ' . $fullStoragePath);

            return redirect()->back()->with('error', 'File not found in the specified directory.');
        }


        // Prepare file details

        $title = $query->files;

        $extension = $query->extension;

        $content = '';


        // Check for password protection

        if ($query->protected === 'YES') {

            $request->validate([

                'password' => 'required|string'

            ]);


            if ($request->password !== $query->password) {

                return redirect()->back()->with('error', 'Incorrect password.');
            }
        }


        // Determine the file content based on its extension

        if ($extension == 'pdf') {

            $content = Storage::url($filePath); // Use the URL for PDF

        } elseif ($extension == 'docx') {

            if (Storage::disk('public')->exists($filePath)) {

                try {

                    $phpWord = \PhpOffice\PhpWord\IOFactory::load(storage_path('app/public/' . $filePath));

                    $tempFile = tempnam(sys_get_temp_dir(), 'phpword');

                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

                    $writer->save($tempFile);

                    $htmlContent = file_get_contents($tempFile);

                    unlink($tempFile);

                    $content = $htmlContent;
                } catch (\Exception $e) {

                    Log::error('Error loading .docx file: ' . $e->getMessage());

                    return redirect()->back()->with('error', 'Error loading .docx file.');
                }
            } else {

                Log::error('File not found at path: ' . $filePath); // Log the file path for debugging

                return redirect()->back()->with('error', 'File not found.');
            }
        }


        return view('read', compact('title', 'query', 'content', 'extension'));
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Decrypt the file ID
        $decryptedId = Crypt::decryptString($id);

        // Query for the file details in the database
        $query = DB::table('users_folder_files')->where(['id' => $decryptedId])->first();

        if (!$query) {
            return redirect()->back()->with('error', 'File not found.');
        }

        // Determine the folder ID to use for path building
        $folderId = $query->users_folder_id;
        if ($query->subfolder_id) {
            // If there's a subfolder, use that ID
            $folderId = $query->subfolder_id;
        }

        // Build the full path using the helper function
        $basePath = "public/users/{$query->users_id}";
        $fullPath = $this->buildFullPath($folderId, $basePath);

        if (!$fullPath || !Storage::exists("$fullPath/{$query->files}")) {
            return redirect()->back()->with('error', 'Folder or file does not exist.');
        }

        // Attempt to get the folder title from the main folder first
        $folder = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first();

        // If the main folder is not found, try to get the name from the subfolder
        if (!$folder) {
            $folder = DB::table('subfolders')->where(['id' => $query->subfolder_id])->first();
            if (!$folder) {
                return redirect()->back()->with('error', 'Folder or subfolder does not exist.');
            }
            $folderTitle = $folder->name; // Use the subfolder's name
        } else {
            $folderTitle = $folder->title; // Use the main folder's title
        }

        $title = $query->files;
        $extension = $query->extension;
        $content = '';

        // Check file extension and handle accordingly
        if ($extension == 'pdf') {
            $filePath = "$fullPath/$title";
            if (Storage::exists($filePath)) {
                $content = $filePath; // Use the full path for PDF
            } else {
                return redirect()->back()->with('error', 'PDF file not found.');
            }
        } elseif ($extension == 'docx') {
            $filePath = "$fullPath/$title"; // Use the full path for DOCX
            if (Storage::exists($filePath)) {
                try {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load(storage_path('app/' . $filePath));
                    $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
                    $writer->save($tempFile);
                    $htmlContent = file_get_contents($tempFile);
                    unlink($tempFile);
                    $content = $htmlContent;
                } catch (\Exception $e) {
                    Log::error('Error loading .docx file: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'Error loading .docx file.');
                }
            } else {
                return redirect()->back()->with('error', 'File not found.');
            }
        } else {
            return redirect()->back()->with('error', 'Unsupported file type.');
        }

        return view('read', compact('title', 'query', 'content', 'extension', 'folderTitle'));
    }


    public function display_pdf($title, $content)
    {
        $path = Crypt::decryptString($content);
        return response()->stream(function () use ($path) {
            echo Storage::disk('local')->get($path);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="$title"',
        ]);
    }


    public function download(Request $request, $id)
    {
        // Validate the password input
        $request->validate([
            'password' => 'required|string'
        ]);

        $password = $request->input('password');
        Log::info('Password entered by user: ' . $password);

        // Query for the file and folder details in the database
        try {
            $decryptedId = Crypt::decryptString($id);
            Log::info('Decrypted ID: ' . $decryptedId);

            $query = DB::table('users_folder_files')->where(['id' => $decryptedId])->first();
            Log::info('File query result: ' . json_encode($query));

            // Determine the folder ID to use for path building
            $folderId = $query->users_folder_id;
            if ($query->subfolder_id) {
                $folderId = $query->subfolder_id;
            }

            // Build the full path
            $basePath = "public/users/{$query->users_id}";
            $fullPath = $this->buildFullPath($folderId, $basePath);

            if (!$fullPath) {
                Log::error("Invalid folder path for ID:", ['folder_id' => $folderId]);
                return response()->json(['error' => 'Folder does not exist.'], 404);
            }

            $title = $query->files; // Original file name
            Log::info('Original file name: ' . $title);

            // Construct the full file path
            $filePath = "$fullPath/$title";
            Log::info('File path: ' . $filePath);

            // Check if the file exists in the storage
            if (!Storage::exists($filePath)) {
                Log::error('File not found in storage.');
                return response()->json(['error' => 'File not found.'], 404);
            }

            Log::info('File exists in storage.');

            // Read the file content
            $fileContent = Storage::get($filePath);

            // Ensure the storage directory exists
            $storagePath = storage_path('app/protected');
            if (!File::exists($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }

            // Create a ZIP file
            $zip = new \ZipArchive();
            $zipFileName = $storagePath . '/protected-file.zip'; // Save the zip file in the storage folder
            Log::info('ZIP file name: ' . $zipFileName);

            if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                Log::info('ZIP file opened successfully.');

                // Add the original content to the zip
                $zip->addFromString($title, $fileContent);

                // Fetch the hashed password from the database
                $correctPassword = $this->getPasswordForFile($query->id); // Fetch the hashed password

                // Hash the provided password and compare
                if (hash('sha256', $password, false) === $correctPassword) {
                    // Password is correct, encrypt the ZIP file
                    Log::info('Password is correct, file will be encrypted.');

                    // Encrypt the ZIP file with the provided password
                    $zip->setEncryptionName($title, \ZipArchive::EM_AES_256, $password);
                } else {
                    // Password is incorrect, do not proceed with download
                    Log::info('Password is incorrect. Download will not proceed.');
                    return response()->json(['error' => 'Incorrect password. Access denied.'], 403);
                }

                $zip->close();
                Log::info('ZIP file closed successfully.');

                // Return the zip file for download with a custom filename
                return response()->download($zipFileName)->deleteFileAfterSend(true);
            } else {
                Log::error('Failed to open the ZIP file.');
                return response()->json(['error' => 'Failed to create the zip file.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error occurred: ' . $e->getMessage());
            return response()->json(['error' => ' An error occurred while processing the request.'], 500);
        }
    }

    private function getPasswordForFile($fileId)
    {
        // Fetch the hashed password from your storage
        return DB::table('users_folder_files')->where('id', $fileId)->value('password'); // Assuming 'password' is the column name
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Decrypt the file ID
        $decryptedId = Crypt::decryptString($id);

        // Query for the file details in the database
        $query = DB::table('users_folder_files')->where(['id' => $decryptedId])->first();

        // Determine the folder ID to use for path building
        $folderId = $query->users_folder_id;
        if ($query->subfolder_id) {
            // If there's a subfolder, use that ID
            $folderId = $query->subfolder_id;
        }

        // Build the full path using the helper function
        $basePath = "public/users/{$query->users_id}";
        $fullPath = $this->buildFullPath($folderId, $basePath);

        if (!$fullPath) {
            return redirect()->back()->with('error', 'Folder does not exist.');
        }

        // Attempt to get the folder title from the main folder first
        $folder = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first();

        // If the main folder is not found, try to get the name from the subfolder
        if (!$folder) {
            $folder = DB::table('subfolders')->where(['id' => $query->subfolder_id])->first();
            if (!$folder) {
                return redirect()->back()->with('error', 'Folder or subfolder does not exist.');
            }
            $folderTitle = $folder->name; // Use the subfolder's name
        } else {
            $folderTitle = $folder->title; // Use the main folder's title
        }

        $title = $query->files;
        $extension = $query->extension;
        $content = '';

        if ($extension == 'docx') {
            $filePath = "$fullPath/$title"; // Use the full path here
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
            }
        }

        return view('edit', compact('query', 'content', 'extension', 'folderTitle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $decryptedId = Crypt::decryptString($id);

        $request->validate([
            'content' => 'required|string',
        ]);

        $query = DB::table('users_folder_files')->where('id', $decryptedId)->first();
        $folder = DB::table('users_folder')->where('id', $query->users_folder_id)->first()->title;
        $title = $query->files;
        $extension = $query->extension;

        $filePath = 'public/users/' . $query->users_id . '/' . $folder . '/' . $title;

        if ($extension == 'docx') {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            $content = $request->input('content');
            $content = '<html><body>' . $content . '</body></html>';

            try {
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $content, true, false);

                $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($tempFile);

                if (Storage::exists($filePath)) {
                    Storage::put($filePath, file_get_contents($tempFile));
                    unlink($tempFile);
                } else {
                    return redirect()->back()->with('error', 'File not found.');
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error processing .docx file: ' . $e->getMessage());
            }
        }

        return redirect()->route('drive.edit', ['id' => Crypt::encryptString($query->id)])
            ->with('message', 'File updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Decrypt the ID to get the file record
        $fileId = Crypt::decryptString($id);

        // Retrieve the file record
        $query = DB::table('users_folder_files')->where('id', $fileId)->first();

        // Check if the file exists in the database
        if (!$query) {
            return back()->with([
                'message' => 'File not found.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }

        // Base directory path for the user
        $baseDirectory = 'users/' . $query->users_id; // Exclude 'public/' prefix

        // Use the helper function to build the full path
        $directory = $this->buildFullPath($query->users_folder_id ?? $query->subfolder_id, $baseDirectory);

        // If the directory path is invalid, return an error
        if (!$directory) {
            return back()->with([
                'message' => 'Folder or subfolder not found.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }

        // Add the file name to the directory path
        $filePath = $directory . '/' . $query->files;

        // Remove 'public/' from the file path to align with Laravel's storage path
        $storagePath = str_replace('public/', '', $filePath);

        // Check if the file exists in storage
        if (Storage::disk('public')->exists($storagePath)) {
            // Delete the file from storage
            Storage::disk('public')->delete($storagePath);

            // Delete the record from the database
            DB::table('users_folder_files')->where('id', $fileId)->delete();

            return back()->with([
                'message' => 'Selected file has been deleted.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'File does not exist in storage.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }



    /**
     * Helper function to build the full path for any level of nested folders.
     */

    public function rename(Request $request, string $id)
    {
        Log::info("Received request data for rename:", $request->all());

        // Validate inputs
        $request->validate([
            'new_name' => 'required|string|max:255'
        ]);

        // Try decrypting the file ID
        try {
            $decryptedId = Crypt::decryptString($id);
            Log::info("Decrypted file ID:", ['id' => $decryptedId]);
        } catch (\Exception $e) {
            Log::error("Failed to decrypt file ID:", ['error' => $e->getMessage()]);
            return response()->json(['type' => 'error', 'message' => 'Failed to decrypt file ID.']);
        }

        // Fetch the file record from the database
        $fileRecord = DB::table('users_folder_files')->where('id', $decryptedId)->first();
        if (!$fileRecord) {
            Log::error("File not found for ID:", ['file_id' => $decryptedId]);
            return response()->json(['type' => 'error', 'message' => 'File not found.']);
        }

        // Determine the folder ID to use for path building
        $folderId = $fileRecord->users_folder_id;
        if ($fileRecord->subfolder_id) {
            // If there's a subfolder, use that ID
            $folderId = $fileRecord->subfolder_id;
        }

        // Build the full path using the helper function
        $basePath = "public/users/{$fileRecord->users_id}";
        $fullPath = $this->buildFullPath($folderId, $basePath);

        if (!$fullPath) {
            Log::error("Invalid folder path for ID:", ['folder_id' => $folderId]);
            return response()->json(['type' => 'error', 'message' => 'Folder does not exist.']);
        }

        $oldFileName = $fileRecord->files;
        $newFileName = $request->input('new_name');

        // Build the full paths for the old and new files
        $oldFilePath = "$fullPath/$oldFileName";
        $newFilePath = "$fullPath/$newFileName";

        Log::info("Old File Path: $oldFilePath, New File Path: $newFilePath");

        // Check if the old file exists
        if (Storage::exists($oldFilePath)) {
            // Rename the file in storage
            Storage::move($oldFilePath, $newFilePath);
            Log::info("File moved in storage from $oldFilePath to $newFilePath");

            // Update the file name in the database
            DB::table('users_folder_files')->where('id', $decryptedId)->update(['files' => $newFileName]);
            Log::info("Database update status for file ID $decryptedId:", ['new_name' => $newFileName]);

            return response()->json(['type' => 'success', 'message' => 'File renamed successfully.']);
        } else {
            Log::warning("Old file path does not exist:", ['path' => $oldFilePath]);
            return response()->json(['type' => 'error', 'message' => 'Old file does not exist.']);
        }
    }
    /**
     * Move a file to another folder.
     */
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

        // If the file is not found, check in the subfolders
        if (!$file) {
            $file = DB::table('subfolders')->where('id', $decryptedFileId)->first();
            if ($file) {
                // If found in subfolders, set the users_folder_id to the parent folder's ID
                $file->users_folder_id = $file->parent_folder_id; // Assuming subfolder has a parent_folder_id
            }
        }

        // Retrieve the destination folder from the users_folder table
        $destinationFolder = DB::table('users_folder')->where('id', $decryptedDestinationFolderId)->first();

        // If the destination folder is not found, check in the subfolders
        if (!$destinationFolder) {
            $destinationFolder = DB::table('subfolders')->where('id', $decryptedDestinationFolderId)->first();
        }

        // Check if both file and destination folder exist
        if (!$file || !$destinationFolder) {
            Log::error('File or destination folder not found.');
            return redirect()->back()->with([
                'message' => 'File or destination folder not found.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }

        // Log the folder title to confirm its value
        Log::info("Destination folder title: " . $destinationFolder->title);

        // Define user ID
        $userId = $file->users_id;

        // Build the source path using the helper function
        $sourceBasePath = "public/users/$userId";
        $sourceFolderPath = $this->buildFullPath($file->users_folder_id, $sourceBasePath);

        // If the source folder path cannot be built, try using the subfolder_id
        if (!$sourceFolderPath) {
            Log::warning("Failed to build source folder path for ID: " . $file->users_folder_id . ". Trying subfolder_id.");
            if ($file->subfolder_id) {
                $sourceFolderPath = $this->buildFullPath($file->subfolder_id, $sourceBasePath);
            }
        }

        if (!$sourceFolderPath) {
            Log::error("Failed to build source folder path using subfolder_id for ID: " . $file->subfolder_id);
            return redirect()->back()->with(['error' => 'Source folder path could not be determined.']);
        }

        $sourcePath = "$sourceFolderPath/{$file->files}";

        // Build the destination path using the helper function
        $destinationBasePath = "public/users/$userId";
        $destinationFolderPath = $this->buildFullPath($decryptedDestinationFolderId, $destinationBasePath);
        if (!$destinationFolderPath) {
            Log::error("Failed to build destination folder path for ID: " . $decryptedDestinationFolderId);
            return redirect()->back()->with(['error' => 'Destination folder path could not be determined.']);
        }
        $destinationPath = "$destinationFolderPath/{$file->files}";

        // Log the constructed paths
        Log::info("Constructed source path: " . $sourcePath);
        Log::info("Constructed destination path: " . $destinationPath);

        // Check if the old file exists
        if (Storage::exists($sourcePath)) {
            // Move the file in storage
            Storage::move($sourcePath, $destinationPath);
            Log::info("File moved in storage from $sourcePath to $destinationPath");

            // Update the file's folder reference in the database
            DB::table('users_folder_files')->where('id', $decryptedFileId)->update([
                'users_folder_id' => $decryptedDestinationFolderId,
            ]);

            Log::info("Database update status for file ID $decryptedFileId:", ['new_folder_id' => $decryptedDestinationFolderId]);

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



    public function paste(Request $request, string $destinationFolderId)
    {
        // Decrypt the destination folder ID
        $decryptedDestinationFolderId = Crypt::decryptString($destinationFolderId);
        $destinationFolder = DB::table('users_folder')->where('id', $decryptedDestinationFolderId)->first();

        if (!$destinationFolder) {
            Log::error("Destination folder not found: " . $decryptedDestinationFolderId);
            return back()->with(['error' => 'Destination folder not found.']);
        }

        // Retrieve the file ID from the request and fetch the file from the database
        $fileId = $request->input('fileId');
        $decryptedFileId = Crypt::decryptString($fileId);
        $copiedFile = DB::table('users_folder_files')->where('id', $decryptedFileId)->first();

        if (!$copiedFile) {
            Log::error("File not found: " . $decryptedFileId);
            return back()->with(['error' => 'File not found.']);
        }

        Log::info("Pasting file: ", ['copiedFile' => $copiedFile]);

        // Determine the folder ID to use for path building
        $folderId = $copiedFile->users_folder_id;
        if ($copiedFile->subfolder_id) {
            // If there's a subfolder, use that ID
            $folderId = $copiedFile->subfolder_id;
        }

        // Build the full path using the helper function
        $basePath = "public/users/{$copiedFile->users_id}";
        $sourceFolderPath = $this->buildFullPath($folderId, $basePath);
        if (!$sourceFolderPath) {
            Log::error("Failed to build source folder path for ID: " . $folderId);
            return back()->with(['error' => 'Source folder path could not be determined.']);
        }

        // Construct the full source path
        $sourcePath = "$sourceFolderPath/{$copiedFile->files}";

        // Generate a new file name using the desired naming convention
        $newFileName = uniqid() . '_' . basename($copiedFile->files);

        // Build the destination path
        $destinationFolderPath = $this->buildFullPath($decryptedDestinationFolderId, $basePath);
        if (!$destinationFolderPath) {
            Log::error("Failed to build destination folder path for ID: " . $decryptedDestinationFolderId);
            return back()->with(['error' => 'Destination folder path could not be determined.']);
        }

        // Construct the full destination path
        $destinationPath = "$destinationFolderPath/$newFileName";

        Log::info("Source path: " . $sourcePath);
        Log::info("Destination path: " . $destinationPath);

        // Copy the file in storage
        if (Storage::exists($sourcePath)) {
            Storage::copy($sourcePath, $destinationPath);
            Log::info("File copied from " . $sourcePath . " to " . $destinationPath);

            // Insert a new record for the copied file
            DB::table('users_folder_files')->insert([
                'users_id' => $copiedFile->users_id,
                'users_folder_id' => $decryptedDestinationFolderId,
                'files' => $newFileName,
                'size' => $copiedFile->size,
                'extension' => $copiedFile->extension,
                'protected' => $copiedFile->protected,
                'password' => $copiedFile->password
            ]);

            Log::info("File pasted successfully.", ['newFileName' => $newFileName]);
            return back()->with(['message' => 'File pasted successfully.', 'type' => 'success', 'title' => 'Success']);
        } else {
            Log::error("Source file not found in storage: " . $sourcePath);
            return back()->with(['error' => 'Source file not found in storage.']);
        }
    }

    // Helper function to generate a unique file name if the file already exists
    private function generateUniqueFileName($fileName, $folderId)
    {
        $fileParts = pathinfo($fileName);
        $baseName = $fileParts['filename']; // Without extension
        $extension = isset($fileParts['extension']) ? '.' . $fileParts['extension'] : ''; // With extension

        // Initialize new name
        $newName = $fileName;
        $counter = 1;

        // Check if a file with the new name already exists in the same folder
        while (DB::table('users_folder_files')
            ->where('users_folder_id', $folderId)
            ->where('files', $newName)
            ->exists()
        ) {
            // Generate a new name e.g. file(1).docx
            $newName = $baseName . "($counter)" . $extension;
            $counter++;
        }

        return $newName;
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
