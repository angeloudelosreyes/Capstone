<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Crypt;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $title = 'Home';
        $query = DB::table('users_folder')->where(['users_id' => auth()->user()->id])->paginate(18);
        $files = [];
        foreach($query as $data) {
            $files[$data->id] = DB::table('users_folder_files')->where(['users_folder_id' => $data->id])->count();
        }
        return view('home',compact('title','query','files'));
    }
}
