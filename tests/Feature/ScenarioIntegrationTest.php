<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use App\Models\Response;
use App\Models\ResponseItem;
use App\Models\CompletionFlag;
use App\Models\SisStudent;
use App\Models\SisCourseRef;
use App\Models\SisEnrollment;
use App\Services\FormManagementService;
use App\Services\FormBuilderService;
use App\Services\ResponseSubmissionService;
use App\Services\CompletionTrackingService;
use App\Services\QaReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScenarioIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SisDataSeeder::class);
    }

    public function test_grade_blocking_scenario_incomplete_student()
    {
        // Setup: Create required form
        $form = $this->createPublishedForm();
        
        $formMgmt = new FormManagementService();
        $formMgmt->assignToAllCourses($form, '202410');

        $tracking = new CompletionTrackingService();
        
        // Student who hasn't completed
        $courses = [['course_reg_no' => 'SE401-202410']];
        $pending = $tracking->getPendingFormsForStudent('2024001', $courses, '202410');

        // Should have pending forms
        $this->assertGreaterThan(0, $pending->count());
        $this->assertFalse($tracking->isFormComplete($form, '2024001', 'SE401-202410', '202410'));

        // Grade blocking logic: if pending forms exist, block grade access
        $canAccessGrades = $pending->count() === 0;
        $this->assertFalse($canAccessGrades);
    }

    public function test_grade_blocking_scenario_completed_student()
    {
        $form = $this->createPublishedForm();
        
        $formMgmt = new FormManagementService();
        $formMgmt->assignToAllCourses($form, '202410');

        // Submit response for student
        $responseService = new ResponseSubmissionService();
        $response = $responseService->createOrResumeDraft($form, '2024001', 'SE401-202410', '202410');
        
        $question = $form->questions->first();
        $responseService->submitResponse($response, [$question->id => 5]);

        // Check completion
        $tracking = new CompletionTrackingService();
        $courses = [['course_reg_no' => 'SE401-202410']];
        $pending = $tracking->getPendingFormsForStudent('2024001', $courses, '202410');

        // Should have no pending forms
        $this->assertEquals(0, $pending->count());
        $this->assertTrue($tracking->isFormComplete($form, '2024001', 'SE401-202410', '202410'));

        // Grade blocking: student can access grades
        $canAccessGrades = $pending->count() === 0;
        $this->assertTrue($canAccessGrades);
    }

    public function test_late_enrollment_handling()
    {
        // Student enrolled late (after form was already published)
        $form = $this->createPublishedForm();
        
        $formMgmt = new FormManagementService();
        $formMgmt->assignToAllCourses($form, '202410');

        // Add late enrollment
        SisEnrollment::create([
            'sis_student_id' => '2024005',
            'course_reg_no' => 'CS301-202410',
            'term_code' => '202410',
            'snapshot_at' => now(),
        ]);

        // Late enrollee should still see the form as required
        $tracking = new CompletionTrackingService();
        $courses = [['course_reg_no' => 'CS301-202410']];
        $required = $tracking->getRequiredFormsForStudent('2024005', $courses, '202410');

        $this->assertGreaterThan(0, $required->count());
    }

    public function test_dropped_course_completion_still_recorded()
    {
        $form = $this->createPublishedForm();
        
        // Student completes form
        $responseService = new ResponseSubmissionService();
        $response = $responseService->createOrResumeDraft($form, '2024002', 'SE401-202410', '202410');
        $question = $form->questions->first();
        $responseService->submitResponse($response, [$question->id => 4]);

        // Student drops course (remove enrollment)
        SisEnrollment::where('sis_student_id', '2024002')
            ->where('course_reg_no', 'SE401-202410')
            ->delete();

        // Completion flag should still exist
        $this->assertDatabaseHas('completion_flags', [
            'sis_student_id' => '2024002',
            'course_reg_no' => 'SE401-202410',
            'form_id' => $form->id,
        ]);

        // Response should still exist
        $this->assertDatabaseHas('responses', [
            'id' => $response->id,
            'status' => 'submitted',
        ]);
    }

    public function test_mcq_multi_response_aggregation()
    {
        // Create form with MCQ multi question
        $formMgmt = new FormManagementService();
        $formBuilder = new FormBuilderService();

        $form = $formMgmt->createForm(['code' => 'MCQ_MULTI_TEST', 'title' => 'MCQ Multi Test', 'form_type' => 'course_feedback']);
        $section = $formBuilder->addSection($form, ['title' => 'Section 1']);
        $question = $formBuilder->addQuestion($section, [
            'prompt' => 'Select all that apply',
            'qtype' => 'mcq_multi',
            'required' => true,
            'order' => 1,
        ]);

        $formMgmt->publishForm($form);

        // Multiple students submit with different selections
        $responseService = new ResponseSubmissionService();

        // Student 1: selects A, B
        $r1 = $responseService->createOrResumeDraft($form, 'S1', 'C1', '202410');
        $responseService->submitResponse($r1, [$question->id => ['A', 'B']]);

        // Student 2: selects A, C
        $r2 = $responseService->createOrResumeDraft($form, 'S2', 'C1', '202410');
        $responseService->submitResponse($r2, [$question->id => ['A', 'C']]);

        // Student 3: selects B
        $r3 = $responseService->createOrResumeDraft($form, 'S3', 'C1', '202410');
        $responseService->submitResponse($r3, [$question->id => ['B']]);

        // Verify response items
        $this->assertEquals(2, ResponseItem::where('response_id', $r1->id)->count());
        $this->assertEquals(2, ResponseItem::where('response_id', $r2->id)->count());
        $this->assertEquals(1, ResponseItem::where('response_id', $r3->id)->count());

        // Test QA reporting aggregation
        $reportingService = new QaReportingService();
        $summary = $reportingService->getResponseSummary($form, 'C1');

        $this->assertArrayHasKey($question->id, $summary);
        $this->assertEquals('mcq_multi', $summary[$question->id]['type']);
        
        // Check counts: A=2, B=2, C=1
        $counts = $summary[$question->id]['counts'];
        $this->assertEquals(2, $counts['A']);
        $this->assertEquals(2, $counts['B']);
        $this->assertEquals(1, $counts['C']);
    }

    public function test_qa_manual_completion_override()
    {
        $form = $this->createPublishedForm();

        // QA officer manually marks completion for a student who couldn't complete online
        $tracking = new CompletionTrackingService();
        $flag = $tracking->markManualCompletion($form->id, '2024003', 'SE401-202410', '202410', 'Technical issues');

        $this->assertDatabaseHas('completion_flags', [
            'form_id' => $form->id,
            'sis_student_id' => '2024003',
            'source' => 'qa_manual',
        ]);

        // Verify it counts as completed
        $this->assertTrue($tracking->isFormComplete($form, '2024003', 'SE401-202410', '202410'));
    }

    // Helper
    protected function createPublishedForm()
    {
        $mgmt = new FormManagementService();
        $builder = new FormBuilderService();

        $form = $mgmt->createForm(['code' => 'SCENARIO_TEST', 'title' => 'Scenario Test', 'form_type' => 'course_feedback']);
        $section = $builder->addSection($form, ['title' => 'Section 1']);
        $builder->addQuestion($section, [
            'prompt' => 'Rate this',
            'qtype' => 'likert',
            'required' => true,
            'scale_min' => 1,
            'scale_max' => 5,
            'order' => 1,
        ]);

        $mgmt->publishForm($form);
        return $form->fresh();
    }
}
