<?php

namespace App\Http\Controllers;

use App\Models\Subfolder;
use App\Models\UsersFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SubfolderController extends Controller
{
    /**
     * Store a new subfolder.
     */
    public function index()
    {
        $title = 'Drive Contents';

        // Fetch subfolders and files
        $subfolders = Subfolder::whereNull('parent_folder_id')->get();
        $files = DB::table('files')->whereNull('folder_id')->get(); // Adjust query as needed

        // Pass both variables to the view
        return view('drive', compact('title', 'subfolders', 'files'));
    }



    public function store(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return back()->withErrors('User not authenticated.');
        }

        try {
            // Decrypt `parent_id` if it's encrypted
            $parentId = Crypt::decryptString($request->parent_id);

            // Merge decrypted `parent_id` into request for validation
            $request->merge(['parent_id' => $parentId]);

            // Validate the request with decrypted `parent_id`
            $request->validate([
                'title' => 'required|string|max:255',
                'parent_id' => 'required|exists:users_folder,id'
            ]);

            // Insert into subfolders table
            DB::table('subfolders')->insert([
                'user_id' => $userId,
                'parent_folder_id' => $parentId,
                'name' => $request->title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('message', 'Subfolder created successfully.');
        } catch (\Exception $e) {
            Log::error('Error inserting subfolder: ' . $e->getMessage());
            return back()->withErrors('Failed to create subfolder. Please try again.');
        }
    }
    /**
     * Display the specified subfolder.
     */
    public function show($id)
    {
        $decryptedId = Crypt::decryptString($id);

        // Fetch the subfolder and related subfolders and files
        $subfolder = Subfolder::find($decryptedId);

        if (!$subfolder) {
            return back()->with([
                'message' => 'Subfolder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Paginate nested subfolders and files
        $nestedSubfolders = $subfolder->subfolders()->paginate(10, ['*'], 'subfolders');
        $files = $subfolder->files()->paginate(10, ['*'], 'files');

        return view('drive', [
            'title' => $subfolder->name,
            'subfolders' => $nestedSubfolders,
            'files' => $files,
            'folderId' => $id
        ]);
    }

    public function showSubfolders($parentId)
    {
        $subfolders = Subfolder::where('parent_folder_id', $parentId)
            ->with('files') // Assuming each subfolder has a 'files' relationship defined
            ->get();

        return view('subfolder', [
            'subfolders' => $subfolders,
            'parentFolderId' => $parentId // Pass the parent ID to the view
        ]);
    }
    public function update(Request $request)
    {
        $old = 'public/users/' . auth()->user()->id . '/' . $request->old;
        $new = 'public/users/' . auth()->user()->id . '/' . $request->new;

        if (Storage::exists($old)) {
            // Move the old directory to the new directory
            Storage::move($old, $new);

            // Update the subfolder name in the database
            DB::table('subfolders')->where(['id' => Crypt::decryptString($request->id)])->update(['name' => $request->new]);

            return back()->with([
                'message' => 'Subfolder has been renamed.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'Old subfolder does not exist.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }


    /**
     * Remove the specified subfolder from storage.
     */
    public function destroy(string $id)
    {
        // Decrypt the subfolder ID
        $decryptedId = Crypt::decryptString($id);

        // Find the subfolder in the database
        $subfolder = Subfolder::find($decryptedId);

        if (!$subfolder) {
            return back()->with([
                'message' => 'Subfolder not found.',
                'type' => 'error',
                'title' => 'System Notification'
            ]);
        }

        // Define the directory path for the subfolder
        $directory = 'public/users/' . auth()->user()->id . '/' . $subfolder->name;

        // Only show a success message if the directory is deleted or if it does not exist
        if (Storage::exists($directory)) {
            Storage::deleteDirectory($directory);
        }

        // Delete the subfolder entry from the database
        $subfolder->delete();

        return back()->with([
            'message' => 'Subfolder has been deleted.',
            'type' => 'success',
            'title' => 'System Notification'
        ]);
    }
}
