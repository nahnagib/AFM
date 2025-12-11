<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StaffRolesSeeder::class,
            UserSeeder::class,
            CourseSeeder::class,
            SisDataSeeder::class,
            DefaultFormsSeeder::class,
        ]);
    }
}
