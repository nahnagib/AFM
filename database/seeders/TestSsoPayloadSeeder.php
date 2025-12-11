<?php

namespace Database\Seeders;

use App\Models\AfmSessionToken;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestSsoPayloadSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test SSO session token for local development
        $termCode = 'Spring 2025';
        
        $courses = [
            [
                'course_reg_no' => 'SE401-Spring2025',
                'course_code' => 'SE401',
                'course_name' => 'Software Engineering Project',
                'dept_name' => 'Software Engineering',
                'college_name' => 'Engineering',
            ],
            [
                'course_reg_no' => 'CS302-Spring2025',
                'course_code' => 'CS302',
                'course_name' => 'Database Systems',
                'dept_name' => 'Computer Science',
                'college_name' => 'Engineering',
            ],
            [
                'course_reg_no' => 'IT210-Spring2025',
                'course_code' => 'IT210',
                'course_name' => 'Computer Networks',
                'dept_name' => 'Information Technology',
                'college_name' => 'Engineering',
            ],
            [
                'course_reg_no' => 'MA201-Spring2025',
                'course_code' => 'MA201',
                'course_name' => 'Discrete Mathematics',
                'dept_name' => 'Mathematics',
                'college_name' => 'Science',
            ],
        ];

        // Create test token
        AfmSessionToken::updateOrCreate(
            ['request_id' => 'test-request-' . Str::uuid()],
            [
                'nonce' => Str::random(32),
                'payload_hash' => hash('sha256', 'test-payload'),
                'sis_student_id' => '1',
                'student_name' => 'Test Student',
                'courses_json' => $courses,
                'role' => 'student',
                'issued_at' => now(),
                'expires_at' => now()->addHours(24),
                'consumed_at' => null,
                'client_ip' => '127.0.0.1',
                'user_agent' => 'Test User Agent',
            ]
        );
    }
}
