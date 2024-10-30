<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Crypt;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class FilesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'fileName' => 'required|string|max:255',
            'fileType' => 'required|string|in:docx',
            'folder_id' => 'required|string',
            'isProtected' => 'nullable|boolean',
            'password' => 'nullable|string|required_if:isProtected,true'
        ]);

        try {
            $folderId = Crypt::decryptString($request->input('folder_id'));
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['error' => 'Invalid folder ID'], 400);
        }

        $fileName = $request->input('fileName');
        $fileType = $request->input('fileType');
        $userId = auth()->user()->id;
        $isProtected = $request->input('isProtected', false);
        $password = $isProtected ? $request->input('password') : null;

        $folder = DB::table('users_folder')->where('id', $folderId)->first();
        if (!$folder) {
            return response()->json(['error' => 'Folder not found'], 404);
        }
        $directory = 'public/users/' . $userId . '/' . $folder->title;

        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        $filePath = $directory . '/' . $fileName . '.' . $fileType;

        if (!Storage::exists($filePath)) {
            if ($fileType === 'docx') {
                $phpWord = new PhpWord();
                $section = $phpWord->addSection();
                $section->addText('');

                $tempFilePath = tempnam(sys_get_temp_dir(), 'phpword');
                $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($tempFilePath);

                Storage::put($filePath, file_get_contents($tempFilePath));
                unlink($tempFilePath);
            }

            $fileSize = Storage::size($filePath);
            DB::table('users_folder_files')->insert([
                'users_id' => $userId,
                'users_folder_id' => $folderId,
                'files' => $fileName . '.' . $fileType,
                'size' => $fileSize,
                'extension' => $fileType,
                'protected' => $isProtected ? 'YES' : 'NO',
                'password' => $password
            ]);

            return response()->json(['fileName' => $fileName], 201);
        } else {
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

        $id = auth()->user()->id;
        $folder_id = Crypt::decryptString($request->folder_id);
        $title = $request->folder;
        $isEncrypted = $request->has('isEncrypted') && $request->isEncrypted;
        $password = $isEncrypted ? $request->password : null;

        Log::info('User ID: ' . $id);
        Log::info('Folder ID: ' . $folder_id);
        Log::info('Folder Title: ' . $title);
        Log::info('Is Encrypted: ' . ($isEncrypted ? 'YES' : 'NO'));
        if ($isEncrypted) {
            Log::info('Password: ' . $password);
        }

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

                DB::table('users_folder_files')->insert([
                    'users_id' => $id,
                    'users_folder_id' => $folder_id,
                    'files' => $name,
                    'size' => $fileSize,
                    'extension' => $extension,
                    'protected' => $isEncrypted ? 'YES' : 'NO',
                    'password' => $password
                ]);
            }
            return back()->with([
                'message' => 'New file has been uploaded.',
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
