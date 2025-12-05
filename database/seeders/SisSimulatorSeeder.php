<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SisStudent;
use App\Models\SisCourse;
use App\Models\SisEnrollment;
use App\Models\SisCourseRef;

class SisSimulatorSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Courses
        $courses = [
            ['CS101', 'Intro to CS', 'CS', '202410', 'IT'],
            ['CS102', 'Data Structures', 'CS', '202410', 'IT'],
            ['ENG101', 'English I', 'ENG', '202410', 'Humanities'],
            ['MATH101', 'Calculus I', 'MATH', '202410', 'Science'],
        ];

        foreach ($courses as $idx => $c) {
            $regNo = 'REG-' . ($idx + 100);
            SisCourse::create([
                'course_reg_no' => $regNo,
                'course_code' => $c[0],
                'course_name' => $c[1],
                'term_code' => $c[3],
                'faculty_name' => $c[4],
            ]);

            // Also seed the cache table for AFM to use
            SisCourseRef::create([
                'course_reg_no' => $regNo,
                'course_code' => $c[0],
                'course_name' => $c[1],
                'dept_name' => $c[2],
                'term_code' => $c[3],
            ]);
        }

        // 2. Create Students
        $students = [
            ['2024001', 'Ali Ahmed', 'ali@limu.edu.ly', 'IT', 'CS'],
            ['2024002', 'Sara Salem', 'sara@limu.edu.ly', 'IT', 'CS'],
            ['2024003', 'Omar Khaled', 'omar@limu.edu.ly', 'Medicine', 'General'],
            ['2024004', 'Fatima Yusuf', 'fatima@limu.edu.ly', 'Business', 'Management'],
            ['2024005', 'Hassan Ali', 'hassan@limu.edu.ly', 'IT', 'SE'],
        ];

        foreach ($students as $s) {
            SisStudent::create([
                'student_id' => $s[0],
                'full_name' => $s[1],
                'email' => $s[2],
                'college' => $s[3],
                'department' => $s[4],
            ]);
        }

        // 3. Enrollments
        $enrollments = [
            '2024001' => ['REG-100', 'REG-101', 'REG-102'],
            '2024002' => ['REG-100', 'REG-102'],
            '2024003' => ['REG-102', 'REG-103'],
            '2024004' => ['REG-102'],
            '2024005' => ['REG-100', 'REG-101', 'REG-103'],
        ];

        foreach ($enrollments as $studentId => $regs) {
            foreach ($regs as $reg) {
                SisEnrollment::create([
                    'student_id' => $studentId,
                    'course_reg_no' => $reg,
                    'term_code' => '202410',
                ]);
            }
        }
    }
}
