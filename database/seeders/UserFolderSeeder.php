<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use Illuminate\Support\Facades\Storage;

class UserFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $query = DB::table('users')->get();
        foreach($query as $row) {
            for($i=1;$i<=6;$i++) {
                $directory = 'public/users/'.$row->id.'/Folder '.$i;
                if (!Storage::exists($directory)) {
                    Storage::makeDirectory($directory);
                    DB::table('users_folder')->insert(['users_id' => $row->id, 'title' => 'Folder '.$i]);
                    echo "Success\n";
                } 
            }
        }
    }
}
