<?php

namespace Database\Seeders;

use App\Models\SisCourseRef;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
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

        foreach ($courses as $course) {
            SisCourseRef::updateOrCreate(
                ['course_reg_no' => $course['course_reg_no']],
                array_merge($course, [
                    'term_code' => $termCode,
                    'last_seen_at' => now(),
                ])
            );
        }
    }
}
