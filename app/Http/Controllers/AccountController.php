<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Crypt;
use Throwable;
use Hash;
class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $title = 'Account';
        // etong DB::table() query builder tawag dyan, isang way para matawag mo yong table na users at kunin lahat ng laman nya gamit get() function
        $query = DB::table('users')->where(['roles' => 'USER'])->get();
        // etong view() at compact(), etong view, dito mo dinidisplay yong files mo na tatawagin na para pag inaccess mo daw yong specific page,  anong file ang makikita mo.
        // yong compact naman, dito mo isesend yong mga gusto mo makita pag inaccess mo yong account na file.
        return view('account',compact('title','query'));
    }
    
    public function profile()
    {
        $title = 'Account';
        return view('profile',compact('title'));
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
        
        // etong request validate, eto yong built in form validation ng laravel, etong required meaning nirerequire mo yong form na meron
        // dapat laman bago mo isend, or else hindi nya i aaccept,  etong unique, eto yong sinasabi na dapat hindi pa existing sa table mo (users)
        // yong email na ilalagay mo kasi kong existing na. ivavalidate nya na like yong email daw ay hindi na available.
        $request->validate([
            'name'       => ['required'],
            'department' => ['required'],
            'email'      => ['required','unique:users,email'],
            'age'        => ['required'],
            'address'    => ['required'],
        ],[
            'name.required'       => 'This field is required',
            'department.required' => 'This field is required',
            'email.required'      => 'This field is required',
            'address.required'    => 'This field is required',
            'age.required'        => 'This field is required',
        ]);
        // etong try and catch, eto yong ginagamit para ivalidate mo yong mismong function na gagawin mo. na sabi e
        // sa try, meaning i tatry mo yong function. if hindi daw nag success, pupunta sya sa catch.  kong baga ang silbe netong try catch 
        // ay para ma validate mo yong function mo if success then proceed sa pag save sa database,  if not, proceed sa fail safe kong saan
        // duon mo ididisplay yong gusto mo idisplay like An error occured.  kasi nga may nag error sa pag save mo.
        try {
            //  etong DB::beginTransact()  eto yong ginagamit para i open mo yong connection para makapag save ka or matawag mo yong table 
            // then saka ka mag insert ng mga gusto mo iinsert sa table na "users"
            DB::beginTransaction();
            $id = DB::table('users')->insert([
                'name'       => $request->name,
                'department' => $request->department,
                'email'      => $request->email,
                'address'    => $request->address,
                'age'        => $request->age,
                'password'   => Hash::make('12345678')
            ]); 
            //Hash::make() eto yong built in function ni laravel na kong saan e yong ilalagay mo na string ay i ha Hash nya.  
            
            
            // etong DB::commit(), after mo daw ma save, need mo daw i commit na like oh nag save ka pero kelangan mo pa i commit or i sure na 
            // yong gagawin mong pag save sa table e mag sesave talaga.
            DB::commit();
            
            // return back()->with(),  eto yong function na gagamitin mo na kong saan e kong na sure mo na na ok na yong pag save, then ano daw gusto mo gawin,
            // so sabi dito sa with(), ang gusto mo gawin ay 
            // 1.  idisplay mo yong message, 
            // 2.  anong type ng message ba to like success ba? failed ba? or what?  
            // 3.  eto yong title ng notification mo na kong saan if nag success, ano yong ididisplay sa kanya na para saan daw yong notification or yong 
            // mag pa popup,
            return back()->with([
                'message' => 'New user has been created.',
                'type'    => 'success',
                'title'   => 'System notification'
            ]);
        } catch (Throwable $th) {
            // DB::rollback(),  eto sya nasa catch. sabi daw e pag failed yong ginawa mo, yong ginagawa mo sa database mo irerevert nya.
            DB::rollback();
            return back()->with([
                'message' => $th->getMessage(),
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
    public function update(Request $request)
    {
        
        $request->validate([
            'name'       => ['required'],
            'department' => ['required'],
            'email'      => ['required','email'],
            'age'        => ['required'],
            'address'    => ['required'],
        ],[
            'name.required'       => 'This field is required',
            'department.required' => 'This field is required',
            'email.required'      => 'This field is required',
            'address.required'    => 'This field is required',
            'age.required'        => 'This field is required',
        ]);
        
        try {
            DB::beginTransaction();
            DB::table('users')->where(['id' => Crypt::decryptString($request->account_id)])->update([
                'name'       => $request->name,
                'department' => $request->department,
                'email'      => $request->email,
                'address'    => $request->address,
                'age'        => $request->age,
            ]); 
            
            DB::commit();
            return back()->with([
                'message' => 'Selected user has been updated.',
                'type'    => 'success',
                'title'   => 'System notification'
            ]);
        
            
        } catch (Throwable $th) {
            DB::rollback();
            return back()->with([
                'message' => $th->getMessage(),
                'type'    => 'error',
                'title'   => 'System Notification'
            ]);
        }
    }
    
    public function update_profile(Request $request)
    {
        
        if(is_null($request->password)) {
            $request->validate([
                'email'          => ['required','email'],
            ],[
                'email.required' => 'This field is required',
            ]);
        } else {
            $request->validate([
                'email'    => ['required'],
                'password' => ['required','confirmed'],
            ],[
                'password.required'   => 'This field is required',
            ]);
        }
        

        try {
            DB::beginTransaction();
            if(is_null($request->password)) {
                DB::table('users')->where(['id' => Crypt::decryptString($request->account_id)])->update(['email' => $request->email]); 
                
                DB::commit();
                return back()->with([
                    'message' => 'Your information has been updated.',
                    'type'    => 'success',
                    'title'   => 'System notification'
                ]);
            } else {
                DB::table('users')->where(['id' => auth()->user()->id])->update([
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                ]); 
                
                DB::commit();
                return back()->with([
                    'message' => 'Information has been updated.',
                    'type'    => 'success',
                    'title'   => 'System notification'
                ]);
            }
            
        } catch (Throwable $th) {
            DB::rollback();
            return back()->with([
                'message' => $th->getMessage(),
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
        $query = DB::table('users')->where(['id' => Crypt::decryptString($id)])->delete();
        if($query) {
            return back()->with([
                'message' => 'Selected user has been deleted.',
                'type'    => 'success',
                'title'   => 'System notification'
            ]);
        }
    }
}
