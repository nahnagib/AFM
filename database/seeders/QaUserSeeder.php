<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class QaUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'QA Officer',
            'email' => 'qa@limu.edu.ly',
            'password' => Hash::make('password'),
            'role' => 'qa_officer',
        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@limu.edu.ly',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}
