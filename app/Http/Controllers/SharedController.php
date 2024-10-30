<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Mail\Notification;
use Illuminate\Support\Facades\Mail;
use Crypt;

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
        if($request->category == 'Individual') {

            $results = DB::table('users')->select('name','id','email')->where('email','=',$request->email);
            if($results->exists()) {
                $query = $results->get();
                foreach($query as $data) {
                    // Mail::to()  eto naman yong built in function ni laravel kong saan mo isesend yong email
                    // yong -send() e eto yong function naman para masend mo yong content ng email.
                    Mail::to($data->email)->send(new Notification($data->email));
                    DB::table('users_shareable_files')->insert([
                        'users_id'              => auth()->user()->id,
                        'recipient_id'          => $data->id, 
                        'users_folder_files_id' => Crypt::decryptString($request->users_folder_files_id)
                    ]);
                }
                return back()->with([
                    'message' => 'Your selected file has been shared.',
                    'type'    => 'success',
                    'title'   => 'System Notification'
                ]);
            } else {
                return back()->with([
                    'message' => 'Email not found.',
                    'type'    => 'error',
                    'title'   => 'System Notification'
                ]);
            }

        } else {
            $query = DB::table('users')->select('name','id','email')->where(['department' => $request->category])->where('email','!=',auth()->user()->email)->get();
            foreach($query as $data) {
                Mail::to($data->email)->send(new Notification($data->email));
                DB::table('users_shareable_files')->insert([
                    'users_id'              => auth()->user()->id,
                    'recipient_id'          => $data->id, 
                    'users_folder_files_id' => Crypt::decryptString($request->users_folder_files_id)
                ]);
            }
            return back()->with([
                'message' => 'Your selected file has been shared.',
                'type'    => 'success',
                'title'   => 'System Notification'
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
