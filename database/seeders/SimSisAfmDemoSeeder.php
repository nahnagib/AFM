<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Form;
use App\Models\CompletionFlag;
use App\Models\SisCourseRef;
use App\Models\SisEnrollment;

class SimSisAfmDemoSeeder extends Seeder
{
    /**
     * Seed AFM demo data aligned with Sim-SIS students
     */
    public function run(): void
    {
        DB::transaction(function () {
            $term = '202410';

            // 1) Create an active course feedback form
            $form = Form::firstOrCreate(
                ['code' => 'DEMO_CF_2024'],
                [
                    'form_type' => 'course_feedback',
                    'title' => 'Demo Course Feedback Form',
                    'is_active' => true,
                ]
            );

            // 2) Get all enrollments from SIS (already seeded by DatabaseSeeder)
            $enrollments = SisEnrollment::with('course')
                ->where('term_code', $term)
                ->get();

            // 3) Sync courses to sis_course_ref for AFM
            foreach ($enrollments->unique('course_reg_no') as $enrollment) {
                SisCourseRef::updateOrCreate(
                    [
                        'course_reg_no' => $enrollment->course_reg_no,
                        'term_code' => $term,
                    ],
                    [
                        'course_code' => $enrollment->course->course_code,
                        'course_name' => $enrollment->course->course_name,
                        'dept_name' => $enrollment->course->faculty_name,
                    ]
                );
            }

            // 4) Create completion flags for each enrollment
            // Demo pattern:
            // - Ali (2024001): All completed
            // - Sara (2024002): Half completed
            // - Others: Pending

            $completionPatterns = [
                '2024001' => 1.0,  // 100% completed
                '2024002' => 0.5,  // 50% completed
                '2024003' => 0.0,  // 0% completed
                '2024004' => 0.0,  // 0% completed
                '2024005' => 0.33, // 33% completed (1 of 3)
            ];

            foreach ($enrollments as $index => $enrollment) {
                $studentId = $enrollment->student_id;
                $completionRate = $completionPatterns[$studentId] ?? 0;

                // Determine if this specific enrollment should be marked complete
                $studentEnrollments = $enrollments->where('student_id', $studentId);
                $studentEnrollmentIndex = $studentEnrollments->search($enrollment);
                $totalForStudent = $studentEnrollments->count();
                $shouldComplete = ($studentEnrollmentIndex / $totalForStudent) < $completionRate;

                CompletionFlag::updateOrCreate(
                    [
                        'form_id' => $form->id,
                        'sis_student_id' => $studentId,
                        'course_reg_no' => $enrollment->course_reg_no,
                        'term_code' => $term,
                    ],
                    [
                        'completed_at' => $shouldComplete ? now()->subDays(rand(1, 10)) : null,
                    ]
                );
            }

            $totalFlags = CompletionFlag::where('term_code', $term)->count();
            $completedFlags = CompletionFlag::where('term_code', $term)
                ->whereNotNull('completed_at')
                ->count();
            $uniqueStudents = CompletionFlag::where('term_code', $term)
                ->distinct('sis_student_id')
                ->count('sis_student_id');
            $uniqueCourses = CompletionFlag::where('term_code', $term)
                ->distinct('course_reg_no')
                ->count('course_reg_no');

            $this->command?->info('Sim-SIS AFM demo data seeded.');
            $this->command?->info("Term: {$term}");
            $this->command?->info("Students: {$uniqueStudents} (2024001-2024005)");
            $this->command?->info("Courses: {$uniqueCourses}");
            $this->command?->info("Completion flags: {$totalFlags}");
            $this->command?->info("Completed: {$completedFlags}, Pending: " . ($totalFlags - $completedFlags));
        });
    }
}
