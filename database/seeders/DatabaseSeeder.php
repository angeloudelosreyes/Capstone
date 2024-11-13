<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an admin user
        \App\Models\User::factory()->create([
            'name'       => 'Angelou',
            'age'        => 22,
            'department' => 'CCS',
            'address'    => 'Rizal',
            'email'      => 'geloudelosreyes@gmail.com',
            'password'   => Hash::make('02_angelou'),
            'roles'      => 'ADMIN',
        ]);

        // Create first additional user
        \App\Models\User::factory()->create([
            'name'       => 'Lafa Flores',
            'age'        => 20,
            'department' => 'CBE',
            'address'    => 'Pangasinan',
            'email'      => 'lafa.flores.up@phinmaed.com',
            'password'   => Hash::make('lancekian12'),
            'roles'      => 'USER',
        ]);

        // Create second additional user
        \App\Models\User::factory()->create([
            'name'       => 'Lance Kian',
            'age'        => 21,
            'department' => 'CCS',
            'address'    => 'Manila',
            'email'      => 'lancekian12@gmail.com',
            'password'   => Hash::make('lancekian12'),
            'roles'      => 'USER',
        ]);
    }
}
