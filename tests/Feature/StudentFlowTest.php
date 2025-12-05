<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Form;
use App\Models\SisStudent;
use App\Models\SisCourseRef;
use App\Models\FormSection;
use App\Models\Question;
use App\Services\FormManagementService;
use App\Services\FormBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SisDataSeeder::class);
    }

    public function test_student_can_access_dashboard()
    {
        // Simulate SSO session
        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'student',
            'afm_user_id' => '2024001',
            'afm_user_name' => 'Ali Ahmed',
            'afm_term_code' => '202410',
            'afm_courses' => [
                ['course_reg_no' => 'SE401-202410', 'course_code' => 'SE401', 'course_name' => 'Software Engineering Project'],
            ],
        ]);

        $response = $this->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('student.dashboard');
        $response->assertViewHas('student');
    }

    public function test_student_can_view_form()
    {
        // Setup
        $formManagement = new FormManagementService();
        $formBuilder = new FormBuilderService();

        $form = $formManagement->createForm([
            'code' => 'TEST_FORM',
            'title' => 'Test Form',
            'form_type' => 'course_feedback',
        ]);

        $section = $formBuilder->addSection($form, ['title' => 'Section 1']);
        $question = $formBuilder->addQuestion($section, [
            'prompt' => 'How satisfied are you?',
            'qtype' => 'likert',
            'required' => true,
            'scale_min' => 1,
            'scale_max' => 5,
        ]);

        $formManagement->publishForm($form);
        $formManagement->assignToAllCourses($form, '202410');

        // Simulate SSO session
        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'student',
            'afm_user_id' => '2024001',
            'afm_term_code' => '202410',
            'afm_courses' => [
                ['course_reg_no' => 'SE401-202410', 'course_code' => 'SE401', 'course_name' => 'Software Engineering Project'],
            ],
        ]);

        $response = $this->get("/student/form/{$form->id}?course=SE401-202410");

        $response->assertStatus(200);
        $response->assertViewIs('student.form');
        $response->assertViewHas('form');
    }

    public function test_student_can_submit_response()
    {
        // Setup
        $formManagement = new FormManagementService();
        $formBuilder = new FormBuilderService();

        $form = $formManagement->createForm([
            'code' => 'SUBMIT_TEST',
            'title' => 'Submit Test',
            'form_type' => 'course_feedback',
        ]);

        $section = $formBuilder->addSection($form, ['title' => 'Section 1']);
        $question = $formBuilder->addQuestion($section, [
            'prompt' => 'Rate this course',
            'qtype' => 'likert',
            'required' => true,
            'scale_min' => 1,
            'scale_max' => 5,
        ]);

        $formManagement->publishForm($form);
        $formManagement->assignToAllCourses($form, '202410');

        // Create student
        SisStudent::updateOrCreate(['sis_student_id' => '2024001'], ['full_name' => 'Ali Ahmed']);

        // Simulate SSO session
        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'student',
            'afm_user_id' => '2024001',
            'afm_term_code' => '202410',
            'afm_courses' => [
                ['course_reg_no' => 'SE401-202410'],
            ],
        ]);

        // Create draft
        $responseService = app(\App\Services\ResponseSubmissionService::class);
        $response = $responseService->createOrResumeDraft($form, '2024001', 'SE401-202410', '202410');

        // Submit
        $submitResponse = $this->postJson("/student/response/{$response->id}/submit", [
            'answers' => [
                $question->id => 5,
            ],
        ]);

        $submitResponse->assertStatus(200);
        $submitResponse->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('responses', [
            'id' => $response->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('completion_flags', [
            'form_id' => $form->id,
            'sis_student_id' => '2024001',
            'course_reg_no' => 'SE401-202410',
        ]);
    }

    public function test_unauthorized_student_cannot_access_qa_routes()
    {
        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'student',
            'afm_user_id' => '2024001',
        ]);

        $response = $this->get('/qa');
        $response->assertStatus(403);
    }
}
