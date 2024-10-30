<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Storage;
use Crypt;

class FolderController extends Controller
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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required']
        ],[
            'title.required' => 'This field is required'
        ]);

        $directory = 'public/users/'.auth()->user()->id.'/'.$request->title;
        // Storage::exists() eto yong way para macheck mo sa file system mo kong existing na ba directory na ina upload mo.
        if (!Storage::exists($directory)) {
            // Storage::makeDirectory(),  eto naman yong way para makapag create ka ng directory if hindi pa existing yung directory na gusto mo gawin.
            Storage::makeDirectory($directory);
            DB::table('users_folder')->insert(['users_id' => auth()->user()->id, 'title' => $request->title]);
            return back()->with([
                'message' => 'New folder has been created.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'Folder already exists.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $query = DB::table('users_folder_files')->where(['users_folder_id' => Crypt::decryptString($id)])->paginate(18);
        $title = DB::table('users_folder')->where(['id' => Crypt::decryptString($id)])->first()->title;
        $folderId = Crypt::encryptString(Crypt::decryptString($id)); // Encrypt the folder ID

        return view('drive', compact('title', 'query', 'folderId'));
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
    public function update(Request $request)
    {
        $old = 'public/users/'.auth()->user()->id.'/'.$request->old;
        $new = 'public/users/'.auth()->user()->id.'/'.$request->new;
        if (Storage::exists($old)) {
            // Storage:move()  eto yong ginagamit para ma move mo anywhere sa file system mo yong file na ginagamit mo.
            Storage::move($old, $new);
            DB::table('users_folder')->where(['id' => Crypt::decryptString($request->id)])->update(['title' => $request->new]);
            return back()->with([
                'message' => 'Folder has been renamed.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'Old folder does not exist.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $query = DB::table('users_folder')->where(['id' => Crypt::decryptString($id)])->first();
        $directory = 'public/users/'.auth()->user()->id.'/'.$query->title;
        if (Storage::exists($directory)) {

            // Storage::deleteDirectory() eto naman yong ginagamit kong saan e gusto mo idelete yong directory naman na ginawa mo
            Storage::deleteDirectory($directory);
            DB::table('users_folder')->where(['id' => Crypt::decryptString($id)])->delete();
            return back()->with([
                'message' => 'Selected folder has been deleted.',
                'type'    => 'success',
                'title'   => 'System Notification'
            ]);
        } else {
            return back()->with([
                'message' => 'Folder does not exist.',
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }
}
