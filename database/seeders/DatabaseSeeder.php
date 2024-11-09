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
            'department' => 'CCS', // CCS CTE CBE
            'address'    => 'Rizal',
            'email'      => 'geloudelosreyes@gmail.com',
            'password'   => Hash::make('02_angelou'),
            'roles'      => 'ADMIN'
        ]);

        // Create two regular users
        \App\Models\User::factory()->create([
            'name'       => 'Lafa Flores',
            'age'        => 25, // Adjust the age if needed
            'department' => 'CCS', // Adjust the department if needed
            'address'    => 'Address1', // Replace with actual address if needed
            'email'      => 'lafa.flores.up@phinmaed.com',
            'password'   => Hash::make('lancekian12'),
            'roles'      => 'USER' // Assuming 'USER' is the role for regular users
        ]);

        \App\Models\User::factory()->create([
            'name'       => 'Lance Kian',
            'age'        => 24, // Adjust the age if needed
            'department' => 'CBE', // Adjust the department if needed
            'address'    => 'Address2', // Replace with actual address if needed
            'email'      => 'lancekian12@gmail.com',
            'password'   => Hash::make('lancekian12'),
            'roles'      => 'USER' // Assuming 'USER' is the role for regular users
        ]);
    }
}
