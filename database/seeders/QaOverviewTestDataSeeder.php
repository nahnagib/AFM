<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Form;
use App\Models\CompletionFlag;
use App\Models\SisCourseRef;

/**
 * TEST ONLY SEEDER
 * This seeder creates synthetic test data (40 students, IDs 100001-100040).
 * DO NOT use for demo environment.
 * For demo, use SimSisAfmDemoSeeder instead.
 */
class QaOverviewTestDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $term = '202410';

            // 1) Create an active course-level form
            $form = Form::firstOrCreate(
                [
                    'code' => 'SEED_CF_2024',
                ],
                [
                    'form_type' => 'course_feedback',
                    'title'     => 'Seeded Course Feedback Form',
                    'is_active' => true,
                ]
            );

            // 2) Create some courses for this term
            $courses = [
                ['course_reg_no' => 'SE401', 'course_code' => 'SE401', 'course_name' => 'Software Engineering Project', 'dept_name' => 'Software Engineering'],
                ['course_reg_no' => 'SE402', 'course_code' => 'SE402', 'course_name' => 'Software Quality Assurance',   'dept_name' => 'Software Engineering'],
                ['course_reg_no' => 'CS101', 'course_code' => 'CS101', 'course_name' => 'Introduction to CS',          'dept_name' => 'Computer Science'],
                ['course_reg_no' => 'MATH201','course_code' => 'MATH201','course_name' => 'Discrete Mathematics',       'dept_name' => 'Mathematics'],
            ];

            foreach ($courses as $c) {
                SisCourseRef::updateOrCreate(
                    [
                        'course_reg_no' => $c['course_reg_no'],
                        'term_code'     => $term,
                    ],
                    [
                        'course_code' => $c['course_code'],
                        'course_name' => $c['course_name'],
                        'dept_name'   => $c['dept_name'],
                    ]
                );
            }

            // 3) Wipe old seeded flags for this term (based on student ID pattern)
            CompletionFlag::where('term_code', $term)
                ->where('sis_student_id', 'like', '10%')
                ->delete();

            // 4) Seed completion flags:
            //    40 students, each student has 4 required evaluations (one per course)
            //    completed_at pattern:
            //      SE401: 80% completed
            //      SE402: 60% completed
            //      CS101: 40% completed
            //      MATH201: 20% completed

            $studentsCount = 40;
            $now = now();

            for ($studentId = 1; $studentId <= $studentsCount; $studentId++) {
                foreach ($courses as $index => $c) {
                    $courseNo = $c['course_reg_no'];

                    // Decide whether this one is "completed"
                    $ratioByCourse = [
                        'SE401'   => 0.80,
                        'SE402'   => 0.60,
                        'CS101'   => 0.40,
                        'MATH201' => 0.20,
                    ];

                    $ratio      = $ratioByCourse[$courseNo] ?? 0.5;
                    $completed  = (mt_rand() / mt_getrandmax()) <= $ratio;
                    $completedAt = $completed ? $now->copy()->subMinutes(mt_rand(0, 3000)) : null;

                    CompletionFlag::create([
                        'form_id'       => $form->id,
                        'sis_student_id'=> (string)(100000 + $studentId), // fake SIS IDs
                        'course_reg_no' => $courseNo,
                        'term_code'     => $term,
                        'completed_at'  => $completedAt,
                    ]);
                }
            }

            $totalFlags = CompletionFlag::where('term_code', $term)
                ->where('sis_student_id', 'like', '10%')
                ->count();
            $completedFlags = CompletionFlag::where('term_code', $term)
                ->where('sis_student_id', 'like', '10%')
                ->whereNotNull('completed_at')
                ->count();

            $this->command?->info("QA Overview test data seeded.");
            $this->command?->info("Term: {$term}, courses: ".count($courses).", completion_flags: {$totalFlags}");
            $this->command?->info("Completed: {$completedFlags}, Pending: ".($totalFlags - $completedFlags));
        });
    }
}
