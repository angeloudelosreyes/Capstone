<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function index()
    {
        $query = DB::table('users_folder_files')->where(['users_id' => auth()->user()->id])->paginate(18);
        $title = 'My Drive';
        return view('mydrive',compact('title','query'));
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
        $query = DB::table('users_folder_files')->where(['id' => Crypt::decryptString($id)])->first();
        $folder = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first()->title;
        $title = $query->files;
        $extension = $query->extension;

        if ($query->protected === 'YES') {
            $request->validate([
                'password' => 'required|string'
            ]);

            if ($request->password !== $query->password) {
                return redirect()->back()->with('error', 'Incorrect password.');
            }
        }

        if ($extension == 'pdf') {
            $content = 'public/users/' . $query->users_id . '/' . $folder . '/' . $title;
        } elseif ($extension == 'docx') {
            $filePath = 'public/users/' . $query->users_id . '/' . $folder . '/' . $title;
            if (Storage::exists($filePath)) {
                try {
                    $phpWord = IOFactory::load(storage_path('app/' . $filePath));
                    $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
                    $writer = IOFactory::createWriter($phpWord, 'HTML');
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
            $content = '';
        }

        return view('read', compact('title', 'query', 'content', 'extension'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $query = DB::table('users_folder_files')->where(['id' => Crypt::decryptString($id)])->first();
        $folder = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first()->title;
        $title = $query->files;
        $extension = $query->extension;

        if ($extension == 'pdf') {
            $content = 'public/users/' . $query->users_id . '/' . $folder . '/' . $title;
        } elseif ($extension == 'docx') {
            $filePath = 'public/users/' . $query->users_id . '/' . $folder . '/' . $title;
            if (Storage::exists($filePath)) {
                try {
                    $phpWord = IOFactory::load(storage_path('app/' . $filePath));
                    $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
                    $writer = IOFactory::createWriter($phpWord, 'HTML');
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
            $content = '';
        }
        return view('read', compact('title', 'query', 'content', 'extension'));
    }
    public function display_pdf($title,$content) {
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

            $folder = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first()->title;
            Log::info('Folder title: ' . $folder);

            $title = $query->files; // Original file name
            Log::info('Original file name: ' . $title);

            $filePath = 'public/users/' . $query->users_id . '/' . $folder . '/' . $title;
            Log::info('File path: ' . $filePath);

            // Check if the file exists in the storage
            if (Storage::exists($filePath)) {
                Log::info('File exists in storage.');

                // Get the full path of the file to be added to the zip
                $fileFullPath = storage_path('app/' . $filePath);
                Log::info('Full file path for ZIP: ' . $fileFullPath);

                // Read the file content and encrypt it
                $fileContent = Storage::get($filePath);
                $encryptedContent = Crypt::encrypt($fileContent);

                // Ensure the storage directory exists
                $storagePath = storage_path('app/protected');
                if (!File::exists($storagePath)) {
                    File::makeDirectory($storagePath, 0755, true);
                }

                // Create a password-protected ZIP file
                $zip = new \ZipArchive();
                $zipFileName = $storagePath . '/protected-file.zip'; // Save the zip file in the storage folder
                Log::info('ZIP file name: ' . $zipFileName);

                if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    Log::info('ZIP file opened successfully.');

                    // Add the encrypted content to the zip with its original file name
                    $zip->addFromString($title, $encryptedContent);
                    Log::info('Encrypted file added to ZIP: ' . $title);

                    $zip->setEncryptionName($title, \ZipArchive::EM_AES_128, $password);
                    Log::info('File encrypted in ZIP with password.');

                    $zip->close();
                    Log::info('ZIP file closed successfully.');

                    Log::info(storage_path('app/protected/protected-file.zip'));

                    // Return the zip file for download with a custom filename
                    return response()->download(storage_path('app/protected/protected-file.zip'))->deleteFileAfterSend(true);
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $query = DB::table('users_folder_files')->where(['id' => Crypt::decryptString($id)])->first();
        $folder = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first()->title;
        $title = $query->files;
        $extension = $query->extension;
        $content = '';

        if ($extension == 'docx') {
            $filePath = 'public/users/' . $query->users_id . '/' . $folder . '/' . $title;
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

        return view('edit', compact('query', 'content', 'extension'));
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
        $query = DB::table('users_folder_files')->where(['id' => Crypt::decryptString($id)])->first();
        $title = DB::table('users_folder')->where(['id' => $query->users_folder_id])->first()->title;
        $directory = 'public/users/'.$query->users_id.'/'.$title.'/'.$query->files;
        if (Storage::exists($directory)) {
            Storage::delete($directory);
            DB::table('users_folder_files')->where(['id' => Crypt::decryptString($id)])->delete();
            return back()->with([
                'message' => 'Selected file has been deleted.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'File does not exist.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }
}
