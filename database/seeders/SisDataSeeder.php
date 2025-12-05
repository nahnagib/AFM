<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SisStudent;
use App\Models\SisCourseRef;
use App\Models\SisEnrollment;

class SisDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Students (Sim-SIS 5 students)
        $students = [
            [
                'sis_student_id' => '2024001',
                'full_name' => 'Ali Ahmed',
                'email' => 'ali.ahmed@limu.edu.ly',
                'college' => 'Engineering',
                'department' => 'Software Engineering',
            ],
            [
                'sis_student_id' => '2024002',
                'full_name' => 'Sara Mohamed',
                'email' => 'sara.mohamed@limu.edu.ly',
                'college' => 'Engineering',
                'department' => 'Software Engineering',
            ],
            [
                'sis_student_id' => '2024003',
                'full_name' => 'Omar Khaled',
                'email' => 'omar.khaled@limu.edu.ly',
                'college' => 'Engineering',
                'department' => 'Software Engineering',
            ],
            [
                'sis_student_id' => '2024004',
                'full_name' => 'Fatima Youssef',
                'email' => 'fatima.youssef@limu.edu.ly',
                'college' => 'Engineering',
                'department' => 'Software Engineering',
            ],
            [
                'sis_student_id' => '2024005',
                'full_name' => 'Khaled Ibrahim',
                'email' => 'khaled.ibrahim@limu.edu.ly',
                'college' => 'Engineering',
                'department' => 'Software Engineering',
            ],
        ];

        foreach ($students as $student) {
            SisStudent::updateOrCreate(
                ['sis_student_id' => $student['sis_student_id']],
                $student
            );
        }

        // 2. Seed Courses
        $termCode = '202410';
        $courses = [
            [
                'course_reg_no' => 'SE401-202410',
                'course_code' => 'SE401',
                'course_name' => 'Software Engineering Project',
                'dept_name' => 'Software Engineering',
            ],
            [
                'course_reg_no' => 'SE402-202410',
                'course_code' => 'SE402',
                'course_name' => 'Quality Assurance',
                'dept_name' => 'Software Engineering',
            ],
            [
                'course_reg_no' => 'CS301-202410',
                'course_code' => 'CS301',
                'course_name' => 'Database Systems',
                'dept_name' => 'Computer Science',
            ],
            [
                'course_reg_no' => 'IT202-202410',
                'course_code' => 'IT202',
                'course_name' => 'Web Development',
                'dept_name' => 'Information Technology',
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

        // 3. Seed Enrollments (All students in all courses for demo simplicity, or varied)
        // Let's vary it slightly
        foreach ($students as $student) {
            foreach ($courses as $index => $course) {
                // Skip one course for some students to create variety
                if ($student['sis_student_id'] == '2024003' && $index == 3) continue;
                if ($student['sis_student_id'] == '2024005' && $index == 0) continue;

                SisEnrollment::updateOrCreate(
                    [
                        'sis_student_id' => $student['sis_student_id'],
                        'course_reg_no' => $course['course_reg_no'],
                        'term_code' => $termCode,
                    ],
                    [
                        'snapshot_at' => now(),
                    ]
                );
            }
        }
    }
}
