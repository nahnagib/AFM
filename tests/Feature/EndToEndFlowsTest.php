<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Form;
use App\Models\SisStudent;
use App\Models\SisCourseRef;
use App\Models\SisEnrollment;
use App\Models\FormCourseScope;
use App\Services\FormManagementService;
use App\Services\FormBuilderService;
use App\Services\ResponseSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EndToEndFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SisDataSeeder::class);
    }

    public function test_complete_student_flow_from_sso_to_completion()
    {
        // 1. Create form
        $formMgmt = new FormManagementService();
        $formBuilder = new FormBuilderService();

        $form = $formMgmt->createForm(['code' => 'E2E_TEST', 'title' => 'E2E Test', 'form_type' => 'course_feedback']);
        $section = $formBuilder->addSection($form, ['title' => 'Section 1']);
        $question = $formBuilder->addQuestion($section, [
            'prompt' => 'Rate this course',
            'qtype' => 'likert',
            'required' => true,
            'scale_min' => 1,
            'scale_max' => 5,
            'order' => 1,
        ]);

        $formMgmt->publishForm($form);
        $formMgmt->assignToAllCourses($form, '202410');

        // 2. Simulate SSO (session setup)
        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'student',
            'afm_user_id' => '2024001',
            'afm_user_name' => 'Ali Ahmed',
            'afm_term_code' => '202410',
            'afm_courses' => [
                ['course_reg_no' => 'SE401-202410', 'course_code' => 'SE401', 'course_name' => 'SE Project'],
            ],
        ]);

        // 3. Access dashboard
        $dashboardResponse = $this->get('/student/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Ali Ahmed');

        // 4. Open form
        $formResponse = $this->get("/student/form/{$form->id}?course=SE401-202410");
        $formResponse->assertStatus(200);
        $formResponse->assertSee($form->title);

        // 5. Create draft (happens automatically on form view)
        $response = app(ResponseSubmissionService::class)->createOrResumeDraft($form, '2024001', 'SE401-202410', '202410');

        // 6. Save draft
        $draftSave = $this->postJson("/student/response/{$response->id}/draft", [
            'answers' => [$question->id => 4],
        ]);
        $draftSave->assertStatus(200);
        $draftSave->assertJson(['success' => true]);

        // 7. Submit response
        $submit = $this->postJson("/student/response/{$response->id}/submit", [
            'answers' => [$question->id => 5],
        ]);
        $submit->assertStatus(200);
        $submit->assertJson(['success' => true]);

        // 8. Verify completion flag
        $this->assertDatabaseHas('completion_flags', [
            'form_id' => $form->id,
            'sis_student_id' => '2024001',
            'course_reg_no' => 'SE401-202410',
            'term_code' => '202410',
        ]);

        // 9. Verify response status
        $this->assertDatabaseHas('responses', [
            'id' => $response->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('response_items', [
            'response_id' => $response->id,
            'question_id' => $question->id,
            'numeric_value' => 5,
        ]);
    }

    public function test_complete_qa_flow_create_to_report()
    {
        // 1. Simulate QA SSO
        session([
            'afm_token_id' => 'qa-token',
            'afm_role' => 'qa_officer',
            'afm_user_id' => 'qa001',
        ]);

        // 2. Access overview
        $overview = $this->get('/qa');
        $overview->assertStatus(200);
        $overview->assertSee('نظرة عامة');

        // 3. Create form
        $formMgmt = new FormManagementService();
        $formBuilder = new FormBuilderService();

        $form = $formMgmt->createForm(['code' => 'QA_E2E', 'title' => 'QA E2E Test', 'form_type' => 'course_feedback']);
        $section = $formBuilder->addSection($form, ['title' => 'Test Section']);
        $formBuilder->addQuestion($section, ['prompt' => 'Test Q', 'qtype' => 'text', 'required' => true, 'order' => 1]);

        // 4. Publish form
        $publish = $this->post("/qa/forms/{$form->id}/publish");
        $publish->assertRedirect();

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'is_published' => true,
        ]);

        // 5. View forms index
        $formsIndex = $this->get('/qa/forms');
        $formsIndex->assertStatus(200);
        $formsIndex->assertSee('QA E2E Test');

        // 6. View reports
        $reports = $this->get('/qa/reports/completion?term=202410');
        $reports->assertStatus(200);
    }

    public function test_admin_config_and_audit_flow()
    {
        session([
            'afm_token_id' => 'admin-token',
            'afm_role' => 'admin',
            'afm_user_id' => 'admin001',
        ]);

        // 1. Access config
        $config = $this->get('/admin/config');
        $config->assertStatus(200);
        $config->assertSee('إعدادات النظام');

        // 2. Update config
        $update = $this->post('/admin/config', [
            'current_term' => '202420',
            'high_risk_threshold' => 0.65,
            'auto_save_interval' => 45,
        ]);
        $update->assertRedirect();

        // 3. View audit logs
        $audit = $this->get('/admin/audit');
        $audit->assertStatus(200);
        $audit->assertSee('سجل التدقيق');
    }

    public function test_sis_api_returns_completion_status()
    {
        // Create completion flag
        \App\Models\CompletionFlag::create([
            'form_id' => 1,
            'sis_student_id' => '2024001',
            'course_reg_no' => 'SE401-202410',
            'term_code' => '202410',
            'completed_at' => now(),
        ]);

        // Create form for reference
        Form::create(['id' => 1, 'code' => 'TEST', 'title' => 'Test Form', 'form_type' => 'course_feedback']);

        // Test API endpoint (requires sanctum auth, so we'll test the controller directly)
        $apiController = new \App\Http\Controllers\Api\SISCompletionApiController();
        $request = new \Illuminate\Http\Request([
            'student_id' => '2024001',
            'term_code' => '202410',
        ]);

        $response = $apiController->getStudentCompletion($request);
        $data = $response->getData(true);

        $this->assertEquals('2024001', $data['student_id']);
        $this->assertEquals('202410', $data['term_code']);
        $this->assertEquals(1, $data['total_completed']);
        $this->assertCount(1, $data['completions']);
    }
}
