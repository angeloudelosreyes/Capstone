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

    }
}
