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
use App\Services\DoubleEncryptionService;


class DriveController extends Controller
{

    protected $encryptionService;

    public function __construct(DoubleEncryptionService $encryptionService)

    {

        $this->encryptionService = $encryptionService; // Inject the service

    }
    /**
     * Display a listing of the resource.
     */
    public function getFolders()
    {
        // Fetch folders belonging to the authenticated user
        $folders = DB::table('users_folder')
            ->where('users_id', auth()->user()->id)
            ->select('id', 'title')
            ->get()
            ->map(function ($folder) {
                // Encrypt the folder ID
                $folder->encrypted_id = Crypt::encryptString($folder->id);
                return $folder;
            });

        $subfolders = DB::table('subfolders')
            ->where('user_id', auth()->user()->id)
            ->select('id', 'name')
            ->get()
            ->map(function ($subfolder) {
                // Encrypt the subfolder ID
                $subfolder->encrypted_id = Crypt::encryptString($subfolder->id);
                return $subfolder;
            });

        // Log the count of subfolders
        Log::info('Number of folders:', ['count' => $folders->count()]);
        Log::info('Number of subfolders:', ['count' => $subfolders->count()]);


        $combinedList = [
            'subfolders' => $subfolders,
            'folders' => $folders,
        ];

        // Return folders and subfolders as separate lists in the response
        return response()->json([
            'folders' => $combinedList,
        ]);
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

            // Verify the provided password against the hashed password in the database
            if (!password_verify($request->password, $query->password)) {
                return redirect()->back()->with('error', 'Incorrect password.');
            }
        }

        // Determine the file content based on its extension
        if ($extension == 'pdf') {
            $content = Storage::url($filePath); // Use the URL for PDF
        } elseif ($extension == 'docx') {
            // Load the encrypted file contents
            $encryptedContent = Storage::disk('public')->get($filePath);

            // Decrypt the file contents using the injected DoubleEncryptionService
            $decryptedContent = $this->encryptionService->decrypt($encryptedContent);

            // Create a temporary file to load with PhpWord
            $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
            file_put_contents($tempFile, $decryptedContent); // Write decrypted content to temp file

            try {
                // Load the decrypted content with PhpWord
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempFile);
                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

                // Save the HTML output to a temporary file
                $htmlTempFile = tempnam(sys_get_temp_dir(), 'phpword_html');
                $writer->save($htmlTempFile);

                // Read the HTML content
                $htmlContent = file_get_contents($htmlTempFile);
                unlink($htmlTempFile); // Clean up temporary file
                unlink($tempFile); // Clean up temporary file
                $content = $htmlContent;
            } catch (\Exception $e) {
                Log::error('Error loading .docx file: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Error loading .docx file.');
            }
        } else {
            return redirect()->back()->with('error', 'Unsupported file type.');
        }

        return view('read', compact('title', 'query', 'content', 'extension'));
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
{
    try {
        // Log the incoming ID to track the request
        Log::info('Received file request for ID: ' . $id);

        // Decrypt the file ID to retrieve it from the database
        $decryptedId = Crypt::decryptString($id);
        Log::info('Decrypted file ID: ' . $decryptedId);

        // Query for the file details in the database
        $query = DB::table('users_folder_files')->where('id', $decryptedId)->first();
        if (!$query) {
            Log::warning('File not found in database for ID: ' . $decryptedId);
            return redirect()->back()->with('error', 'File not found.');
        }

        // Log the query result
        Log::info('File found in database:', ['file' => $query]);

        // Determine the folder ID to use for path building
        $folderId = $query->users_folder_id;
        if ($query->subfolder_id) {
            $folderId = $query->subfolder_id; // Use the subfolder if available
        }

        // Build the full path using the helper function
        $basePath = "public/users/{$query->users_id}";
        $fullPath = $this->buildFullPath($folderId, $basePath);
        Log::info('Built full path for file: ' . $fullPath);

        // Check if file exists
        if (!$fullPath || !Storage::exists("$fullPath/{$query->files}")) {
            Log::warning('File does not exist at path: ' . $fullPath . '/' . $query->files);
            return redirect()->back()->with('error', 'File does not exist.');
        }

        // Attempt to get the folder title
        $folder = DB::table('users_folder')->where('id', $query->users_folder_id)->first();
        if (!$folder) {
            $folder = DB::table('subfolders')->where('id', $query->subfolder_id)->first();
            if (!$folder) {
                Log::warning('Folder or subfolder not found for file: ' . $query->files);
                return redirect()->back()->with('error', 'Folder or subfolder does not exist.');
            }
            $folderTitle = $folder->name; // Use the subfolder name
        } else {
            $folderTitle = $folder->title; // Use the main folder's title
        }

        // Log folder title
        Log::info('Folder title: ' . $folderTitle);

        $title = $query->files;
        $extension = $query->extension;
        $content = '';

        // Check the file extension and handle accordingly
        if ($extension == 'pdf') {
            $filePath = "$fullPath/$title";
            if (Storage::exists($filePath)) {
                $content = $filePath; // Set PDF content to the file path
            } else {
                Log::warning('PDF file not found at path: ' . $filePath);
                return redirect()->back()->with('error', 'PDF file not found.');
            }
        } elseif ($extension == 'docx') {
            // Handle DOCX files here (using PhpWord or similar)
            $content = $this->handleDocx($fullPath, $title);
        } else {
            Log::warning('Unsupported file type: ' . $extension);
            return redirect()->back()->with('error', 'Unsupported file type.');
        }

        // Log success before returning the view
        Log::info('Returning view for file: ' . $title);
        return view('read', compact('title', 'content', 'extension', 'folderTitle'));
    } catch (\Exception $e) {
        // Log the error details
        Log::error('Error in show method:', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'An error occurred while retrieving the file.');
    }
}

private function handleDocx($fullPath, $title)
{
    try {
        Log::info('Handling DOCX file for: ' . $title);

        $filePath = "$fullPath/$title";
        if (Storage::exists($filePath)) {
            $encryptedContent = Storage::get($filePath);
            Log::info('DOCX file found, decrypting content for: ' . $title);

            // Decrypt the file contents (assuming DoubleEncryptionService is correctly set up)
            $decryptedContent = $this->encryptionService->decrypt($encryptedContent);

            // Use PhpWord to convert DOCX to HTML (you need the PhpOffice\PhpWord library)
            $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
            file_put_contents($tempFile, $decryptedContent); // Write decrypted content to temp file
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempFile);
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

            // Save HTML output to a temporary file
            $htmlTempFile = tempnam(sys_get_temp_dir(), 'phpword_html');
            $writer->save($htmlTempFile);

            // Read the HTML content
            $htmlContent = file_get_contents($htmlTempFile);
            unlink($htmlTempFile); // Clean up temporary file
            unlink($tempFile); // Clean up temporary file

            Log::info('DOCX content successfully converted to HTML for: ' . $title);

            return $htmlContent;
        } else {
            Log::warning('DOCX file not found at path: ' . $filePath);
            return redirect()->back()->with('error', 'DOCX file not found.');
        }
    } catch (\Exception $e) {
        // Log DOCX handling error
        Log::error('Error processing DOCX file: ' . $e->getMessage(), ['file' => $title, 'stack' => $e->getTraceAsString()]);
        return redirect()->back()->with('error', 'Error loading DOCX file.');
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

        // Check if the file is protected and handle accordingly
        if ($query->protected === 'YES') {
            // Fetch the hashed password from the database using the helper function
            $correctPassword = $this->getPasswordForFile($decryptedId);

            // Verify the provided password against the hashed password
            if (!password_verify($password, $correctPassword)) {
                Log::info('Password is incorrect. Download will not proceed.');
                return response()->json(['error' => 'Incorrect password. Access denied.'], 403);
            }
        }

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

            // Encrypt the file content with the provided password
            $encryptedContent = $this->encryptionService->encrypt($fileContent);

            // Add the encrypted content to the zip
            $zip->addFromString($title, $encryptedContent);

            // Encrypt the ZIP file with the provided password
            $zip->setEncryptionName($title, \ZipArchive::EM_AES_256, $password);

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
        return response()->json(['error' => 'An error occurred while processing the request.'], 500);
    }
}

public function display_pdf($title, $content)
{
    try {
        // Decrypt the content to get the actual file path
        $decryptedPath = Crypt::decryptString($content);

        // Remove 'public/' prefix from decrypted path if present
        $decryptedPath = preg_replace('/^public\//', '', $decryptedPath);
        Log::info('Decrypted PDF path without public prefix:', ['path' => $decryptedPath]);

        // Normalize path to handle any potential issues with case sensitivity
        $normalizedPath = strtolower($decryptedPath);
        Log::info('Normalized PDF path for storage check:', ['path' => $normalizedPath]);

        // Check if the file exists in storage
        if (!Storage::disk('public')->exists($normalizedPath)) {
            Log::warning('PDF file not found in storage:', ['path' => $normalizedPath]);
            return response()->json(['error' => 'PDF file not found in storage.'], 404);
        }
        Log::info('PDF file exists in storage:', ['path' => $normalizedPath]);

        // Retrieve the encrypted file contents
        $encryptedContent = Storage::disk('public')->get($normalizedPath);
        Log::info('Encrypted content retrieved from storage.');

        // Use the DoubleEncryptionService to decrypt the content
        $decryptedContent = $this->encryptionService->decrypt($encryptedContent);
        Log::info('PDF content successfully decrypted.');

        // Create a temporary decrypted file
        $tempPdfPath = tempnam(sys_get_temp_dir(), 'decrypted_pdf') . '.pdf';
        file_put_contents($tempPdfPath, $decryptedContent);
        Log::info('Temporary decrypted PDF file created:', ['tempPath' => $tempPdfPath]);

        // Serve the decrypted PDF file
        return response()->file($tempPdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $title . '"',
        ])->deleteFileAfterSend(true); // Delete the temp file after sending
    } catch (\Exception $e) {
        Log::error('Error displaying PDF:', [
            'error' => $e->getMessage(),
            'stack' => $e->getTraceAsString(),
            'decryptedPath' => $decryptedPath ?? 'N/A'
        ]);
        return redirect()->back()->with('error', 'An error occurred while displaying the PDF: ' . $e->getMessage());
    }
}




private function getPasswordForFile($fileId)
{
    // Fetch the hashed password from your storage
    return DB::table('users_folder_files')->where('id', $fileId)->value ('password'); // Assuming 'password' is the column name
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
                    // Load the encrypted file contents
                    $encryptedContent = Storage::get($filePath);

                    // Decrypt the file contents using your DoubleEncryptionService
                    $doubleEncryptionService = new DoubleEncryptionService(); // Ensure you have this service available
                    $decryptedContent = $doubleEncryptionService->decrypt($encryptedContent);

                    // Write the decrypted content to a temporary file
                    $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
                    file_put_contents($tempFile, $decryptedContent); // Write decrypted content to temp file

                    // Load the decrypted content with PhpWord
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempFile);
                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

                    // Save the HTML output to a temporary file
                    $htmlTempFile = tempnam(sys_get_temp_dir(), 'phpword_html');
                    $writer->save($htmlTempFile);

                    // Read the HTML content
                    $content = file_get_contents($htmlTempFile);
                    unlink($htmlTempFile); // Clean up temporary file
                    unlink($tempFile); // Clean up temporary file
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', 'Error loading .docx file: ' . $e->getMessage());
                }
            } else {
                return redirect()->back()->with('error', 'File not found.');
            }
        }

        return view('edit', compact('query', 'content', 'extension', 'folderTitle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Decrypt the file ID
        $decryptedId = Crypt::decryptString($id);

        // Validate the incoming request
        $request->validate([
            'content' => 'required|string',
        ]);

        // Query for the file details in the database
        $query = DB::table('users_folder_files')->where('id', $decryptedId)->first();

        if (!$query) {
            return redirect()->back()->with('error', 'File not found.');
        }

        // Get the folder title and other details
        $folder = DB::table('users_folder')->where('id', $query->users_folder_id)->first();
        $folderTitle = $folder ? $folder->title : 'Default Folder'; // Handle case where folder might not exist
        $title = $query->files;
        $extension = $query->extension;

        // Construct the file path
        $filePath = 'public/users/' . $query->users_id . '/' . $folderTitle . '/' . $title;

        if ($extension == 'docx') {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Prepare the content for the document
            $content = $request->input('content');
            $content = '<html><body>' . $content . '</body></html>';

            try {
                // Add HTML content to the section
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $content, true, false);

                // Save the new document to a temporary file
                $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($tempFile);

                // Read the content of the temporary file
                $fileContent = file_get_contents($tempFile);
                unlink($tempFile); // Clean up temporary file

                // Encrypt the file content using DoubleEncryptionService
                $doubleEncryptionService = new DoubleEncryptionService();
                $encryptedContent = $doubleEncryptionService->encrypt($fileContent);

                // Check if the original file exists
                if (Storage::exists($filePath)) {
                    // Update the file with the new encrypted content
                    Storage::put($filePath, $encryptedContent);
                } else {
                    return redirect()->back()->with('error', 'File not found.');
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error processing .docx file: ' . $e->getMessage());
            }
        }

        // Encrypt the ID to redirect back to the edit page
        $encryptedId = Crypt::encryptString($query->id);

        // Log the successful update and redirect
        Log::info('File updated successfully. Redirecting to edit page for ID: ' . $encryptedId);

        return redirect()->route('drive.edit', ['id' => $encryptedId])
            ->with('message', 'File updated successfully.');
    }

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
        if (!$file) {
            Log::error("File with ID $decryptedFileId not found in database.");
            return redirect()->back()->with([
                'message' => 'File not found.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }

        // Retrieve the destination folder (main folder or subfolder)
        $destinationFolder = DB::table('users_folder')->where('id', $decryptedDestinationFolderId)->first();
        $isMainFolder = true;
        if (!$destinationFolder) {
            $destinationFolder = DB::table('subfolders')->where('id', $decryptedDestinationFolderId)->first();
            $isMainFolder = false;
        }

        if (!$destinationFolder) {
            Log::error('Destination folder not found.');
            return redirect()->back()->with([
                'message' => 'Destination folder not found.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }

        // Define user ID and construct paths without the `public/` prefix
        $userId = $file->users_id;
        $sourcePath = $file->file_path;  // Path stored in database should already be relative (without `public/`)
        Log::info("Constructed source path: " . $sourcePath);

        // Determine the destination path within `storage/app/public`
        if ($isMainFolder) {
            $destinationFolderPath = str_replace('public/', '', $destinationFolder->folder_path); // Remove any lingering `public/`
        } else {
            // For subfolders, get the path from subfolder record and remove `public/`
            $destinationFolderPath = str_replace('public/', '', $destinationFolder->subfolder_path);
        }
        $destinationPath = "$destinationFolderPath/{$file->files}";

        Log::info("Constructed destination path: " . $destinationPath);

        // Move the file using the `public` disk
        if (Storage::disk('public')->exists($sourcePath)) {
            Storage::disk('public')->move($sourcePath, $destinationPath);
            Log::info("File moved in storage from $sourcePath to $destinationPath");

            // Update the file's folder reference and file path in the database
            DB::table('users_folder_files')->where('id', $decryptedFileId)->update([
                'users_folder_id' => $isMainFolder ? $decryptedDestinationFolderId : null,
                'subfolder_id' => !$isMainFolder ? $decryptedDestinationFolderId : null,
                'file_path' => $destinationPath
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
        Log::info("Received encrypted destinationFolderId: " . $destinationFolderId);

        try {
            // Decrypt the destination folder ID
            $decryptedDestinationFolderId = Crypt::decryptString($destinationFolderId);
            Log::info("Decrypted destinationFolderId: " . $decryptedDestinationFolderId);
        } catch (\Exception $e) {
            Log::error("Decryption failed: " . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Invalid encrypted destination folder ID.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }

        // Retrieve the destination folder (main folder or subfolder)
        $destinationFolder = DB::table('users_folder')->where('id', $decryptedDestinationFolderId)->first();
        $isMainFolder = true;
        if (!$destinationFolder) {
            $destinationFolder = DB::table('subfolders')->where('id', $decryptedDestinationFolderId)->first();
            $isMainFolder = false;
        }

        if (!$destinationFolder) {
            Log::error("Destination folder not found: " . $decryptedDestinationFolderId);
            return redirect()->back()->with([
                'message' => 'Destination folder not found.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }

        // Retrieve the file ID from the request and fetch the file from the database
        $fileId = $request->input('fileId');
        $decryptedFileId = Crypt::decryptString($fileId);
        $copiedFile = DB::table('users_folder_files')->where('id', $decryptedFileId)->first();

        if (!$copiedFile) {
            Log::error("File not found: " . $decryptedFileId);
            return redirect()->back()->with([
                'message' => 'File not found.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }

        Log::info("Pasting file: ", ['copiedFile' => $copiedFile]);

        // Define source path and ensure it follows the correct format
        $sourcePath = $copiedFile->file_path;
        Log::info("Source path: " . $sourcePath);

        // Generate a unique file name to avoid conflicts
        $newFileName = $this->generateUniqueFileName($copiedFile->files);

        // Build the destination path based on main or subfolder
        if ($isMainFolder) {
            $destinationFolderPath = str_replace('public/', '', $destinationFolder->folder_path);
        } else {
            $destinationFolderPath = str_replace('public/', '', $destinationFolder->subfolder_path);
        }
        $destinationPath = "$destinationFolderPath/$newFileName";

        Log::info("Constructed destination path: " . $destinationPath);

        // Copy the file in storage
        if (Storage::disk('public')->exists($sourcePath)) {
            Storage::disk('public')->copy($sourcePath, $destinationPath);
            Log::info("File copied in storage from $sourcePath to $destinationPath");

            // Insert a new record for the copied file with the unique file name
            DB::table('users_folder_files')->insert([
                'users_id' => $copiedFile->users_id,
                'users_folder_id' => $isMainFolder ? $decryptedDestinationFolderId : null,
                'subfolder_id' => !$isMainFolder ? $decryptedDestinationFolderId : null,
                'files' => $newFileName,
                'size' => $copiedFile->size,
                'extension' => $copiedFile->extension,
                'protected' => $copiedFile->protected,
                'password' => $copiedFile->password,
                'file_path' => $destinationPath
            ]);

            Log::info("File pasted successfully.", ['newFileName' => $newFileName]);
            return redirect()->back()->with([
                'message' => 'File pasted successfully.',
                'type' => 'success',
                'title' => 'Success'
            ]);
        } else {
            Log::error("Source file not found in storage: " . $sourcePath);
            return redirect()->back()->with([
                'message' => 'Source file not found in storage.',
                'type' => 'error',
                'title' => 'Error'
            ]);
        }
    }


    // Helper function to generate a unique file name if the file already exists
    private function generateUniqueFileName($fileName)
    {
        $fileParts = pathinfo($fileName);
        $baseName = $fileParts['filename']; // Name without extension
        $extension = isset($fileParts['extension']) ? '.' . $fileParts['extension'] : ''; // Extension with dot

        $newName = $fileName; // Start with the original name
        $counter = 1;

        // Check for duplicate file names in the database globally
        while (DB::table('users_folder_files')
            ->where('files', $newName)
            ->exists()
        ) {
            // If the name exists, append (counter) to the base name
            $newName = "{$baseName} ({$counter}){$extension}";
            $counter++;
        }

        return $newName; // Return the unique file name
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
