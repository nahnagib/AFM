<?php

namespace App\Services;

use App\Models\Response;
use App\Models\AfmFormTemplate;
use Illuminate\Support\Facades\Log;

class FeedbackService
{
    /**
     * Start or retrieve a response for a specific course context.
     *
     * @param int $formTemplateId
     * @param string $sisStudentId
     * @param string|null $courseRegNo
     * @param string $termCode
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function startResponse(
        int $formTemplateId,
        string $sisStudentId,
        ?string $courseRegNo,
        string $termCode
    ): Response {
        // 1. Load Template to check type
        $template = AfmFormTemplate::findOrFail($formTemplateId);

        // 2. Validate Context based on Form Type
        if ($template->form_type === 'course') {
            if (empty($courseRegNo) || $courseRegNo === 'system') {
                throw new \InvalidArgumentException('Course registration number is required for course feedback forms.');
            }
        }

        // 3. Normalize course_reg_no for system forms
        if ($template->form_type === 'system') {
            // System forms should not have a course_reg_no
            // But if the DB requires it to be nullable, we set it to null.
            // If the DB requires it to be NOT NULL, we might need a dummy value or fix the schema.
            // Based on user feedback, we assume system forms can handle null or we shouldn't be here if schema is strict.
            // However, the user said "responses.course_reg_no is NOT NULL in the DB schema (correct for course-scoped forms)".
            // This implies it MIGHT be nullable for others, or we need to fix it.
            // Let's check the migration `2025_11_28_183820_create_responses_table.php` again.
            // It says: `$table->string('course_reg_no');` which implies NOT NULL.
            // So for system forms, we MUST provide a value if we use the same table.
            // Or maybe system forms use a different mechanism?
            // The `StudentDashboardController` passes 'system' as course_reg_no for system forms in the URL.
            // So if we receive 'system', we should probably store 'system' if the DB requires a string.
            
            if (empty($courseRegNo) || $courseRegNo === 'system') {
                 // If DB requires string, 'system' is a safe placeholder for global forms
                 $courseRegNo = 'system';
            }
        }

        // 4. Get or Create Response
        // We use firstOrCreate to ensure idempotency
        $response = Response::firstOrCreate([
            'form_template_id' => $formTemplateId,
            'sis_student_id' => $sisStudentId,
            'course_reg_no' => $courseRegNo,
            'term_code' => $termCode,
        ], [
            'status' => 'not_started',
            'student_hash' => hash('sha256', $sisStudentId),
            'last_active_at' => now(),
        ]);

        return $response;
    }
}
