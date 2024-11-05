<?php

namespace App\Http\Controllers;

use App\Models\Subfolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubfolderController extends Controller
{
    /**
     * Store a new subfolder.
     */
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
    public function show(Subfolder $subfolder)
    {
        $subfolders = $subfolder->subfolders;
        $files = DB::table('files')->where('folder_id', $subfolder->id)->get();

        return view('subfolders.show', [
            'subfolder' => $subfolder,
            'subfolders' => $subfolders,
            'files' => $files,
            'parentFolderId' => $subfolder->id // Pass the current folder ID as parent ID
        ]);
    }
    public function showSubfolders($parentId)
    {
        $subfolders = DB::table('subfolders')->where('parent_folder_id', $parentId)->get();
        $query = Subfolder::hydrate($subfolders->toArray()); // Convert stdClass objects to Subfolder models
        return view('your_view_name', compact('query'));
    }
}
