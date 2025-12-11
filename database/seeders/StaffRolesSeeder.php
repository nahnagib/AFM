<?php

namespace Database\Seeders;

use App\Models\StaffRole;
use Illuminate\Database\Seeder;

class StaffRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_key' => 'lab_supervisor', 'label_ar' => 'مشرف معمل الحاسوب'],
            ['role_key' => 'pbl_supervisor', 'label_ar' => 'المشرف التعليمي'],
            ['role_key' => 'academic_advisor', 'label_ar' => 'المرشد الأكاديمي'],
        ];

        foreach ($roles as $roleData) {
            StaffRole::firstOrCreate(
                ['role_key' => $roleData['role_key']],
                ['label_ar' => $roleData['label_ar'], 'is_active' => true]
            );
        }
    }
}
