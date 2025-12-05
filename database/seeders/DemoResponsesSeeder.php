<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\SisStudent;
use App\Models\SisEnrollment;
use App\Models\Response;
use App\Models\CompletionFlag;

class DemoResponsesSeeder extends Seeder
{
    public function run(): void
    {
        $termCode = '202410';
        $courseForm = Form::where('code', 'COURSE_FALL_2024')->first();
        
        if (!$courseForm) return;

        // 1. Ali (2024001) - Completed Everything
        $ali = SisStudent::where('sis_student_id', '2024001')->first();
        $enrollments = SisEnrollment::where('sis_student_id', '2024001')->get();

        foreach ($enrollments as $enrollment) {
            $this->createResponse($courseForm, $ali, $enrollment->course_reg_no, $termCode, 'submitted');
        }

        // 2. Sara (2024002) - Completed 50%
        $sara = SisStudent::where('sis_student_id', '2024002')->first();
        $enrollments = SisEnrollment::where('sis_student_id', '2024002')->get();
        
        foreach ($enrollments as $index => $enrollment) {
            if ($index % 2 == 0) {
                $this->createResponse($courseForm, $sara, $enrollment->course_reg_no, $termCode, 'submitted');
            } else {
                // Draft
                $this->createResponse($courseForm, $sara, $enrollment->course_reg_no, $termCode, 'draft');
            }
        }

        // 3. Omar (2024003) - No responses (Pending)
    }

    private function createResponse($form, $student, $courseRegNo, $termCode, $status)
    {
        $response = Response::firstOrCreate(
            [
                'form_id' => $form->id,
                'sis_student_id' => $student->sis_student_id,
                'course_reg_no' => $courseRegNo,
                'term_code' => $termCode,
            ],
            [
                'status' => $status,
                'submitted_at' => $status === 'submitted' ? now() : null,
            ]
        );

        // Only create items if newly created or no items
        if ($response->wasRecentlyCreated || $response->items()->count() == 0) {
            // Create dummy items
            foreach ($form->questions as $question) {
                $response->items()->create([
                    'question_id' => $question->id,
                    'numeric_value' => $question->qtype === 'likert' ? rand(3, 5) : null,
                    'text_value' => $question->qtype === 'textarea' ? 'Good course.' : null,
                ]);
            }
        }

        if ($status === 'submitted') {
            CompletionFlag::markComplete(
                $form->id,
                $student->sis_student_id,
                $courseRegNo,
                $termCode
            );
        }
    }
}
