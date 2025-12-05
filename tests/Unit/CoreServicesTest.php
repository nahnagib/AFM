<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Form;
use App\Models\SisStudent;
use App\Models\SisCourseRef;
use App\Services\FormManagementService;
use App\Services\FormBuilderService;
use App\Services\ResponseSubmissionService;
use App\Services\CompletionTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoreServicesTest extends TestCase
{
    use RefreshDatabase;

    protected $formManagement;
    protected $formBuilder;
    protected $responseSubmission;
    protected $completionTracking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formManagement = new FormManagementService();
        $this->formBuilder = new FormBuilderService();
        $this->responseSubmission = new ResponseSubmissionService();
        $this->completionTracking = new CompletionTrackingService();
    }

    public function test_form_lifecycle()
    {
        // 1. Create Form
        $form = $this->formManagement->createForm([
            'code' => 'TEST_FORM',
            'title' => 'Test Form',
            'form_type' => 'course_feedback',
        ]);

        $this->assertDatabaseHas('forms', ['code' => 'TEST_FORM']);
        $this->assertFalse($form->is_published);

        // 2. Add Structure
        $section = $this->formBuilder->addSection($form, ['title' => 'Section 1']);
        $question = $this->formBuilder->addQuestion($section, [
            'prompt' => 'Q1',
            'qtype' => 'likert',
            'required' => true,
            'scale_min' => 1,
            'scale_max' => 5,
        ]);

        $this->assertCount(1, $form->sections);
        $this->assertCount(1, $section->questions);

        // 3. Publish
        $this->formManagement->publishForm($form);
        $this->assertTrue($form->fresh()->is_published);
    }

    public function test_response_submission_flow()
    {
        // Setup Form
        $form = $this->formManagement->createForm(['code' => 'RESP_TEST', 'title' => 'Resp Test', 'form_type' => 'course_feedback']);
        $section = $this->formBuilder->addSection($form, ['title' => 'S1']);
        $q1 = $this->formBuilder->addQuestion($section, ['prompt' => 'Q1', 'qtype' => 'likert', 'required' => true, 'scale_min' => 1, 'scale_max' => 5]);
        $this->formManagement->publishForm($form);

        // Setup Student & Course
        $student = SisStudent::create(['sis_student_id' => 'STU001', 'full_name' => 'Test Student']);
        $course = SisCourseRef::create(['course_reg_no' => 'CRS001', 'course_code' => 'CS101', 'course_name' => 'Intro', 'term_code' => '202410', 'last_seen_at' => now()]);

        // 1. Create Draft
        $response = $this->responseSubmission->createOrResumeDraft($form, 'STU001', 'CRS001', '202410');
        $this->assertEquals('draft', $response->status);

        // 2. Save Draft
        $this->responseSubmission->saveDraft($response, [$q1->id => 3]);
        $this->assertDatabaseHas('response_items', ['response_id' => $response->id, 'numeric_value' => 3]);

        // 3. Submit
        $this->responseSubmission->submitResponse($response, [$q1->id => 5]);
        $this->assertEquals('submitted', $response->fresh()->status);
        $this->assertNotNull($response->fresh()->submitted_at);
        
        // 4. Verify Completion Flag
        $this->assertTrue($this->completionTracking->isFormComplete($form, 'STU001', 'CRS001', '202410'));
    }

    public function test_completion_tracking()
    {
        // Setup
        $term = '202410';
        $form = $this->formManagement->createForm(['code' => 'TRACK_TEST', 'title' => 'Track Test', 'form_type' => 'course_feedback']);
        $section = $this->formBuilder->addSection($form, ['title' => 'S1']);
        $q1 = $this->formBuilder->addQuestion($section, ['prompt' => 'Q1', 'qtype' => 'likert', 'required' => true, 'scale_min' => 1, 'scale_max' => 5]);
        $this->formManagement->publishForm($form);

        $student = SisStudent::create(['sis_student_id' => 'STU002', 'full_name' => 'Test Student 2']);
        $course = SisCourseRef::create(['course_reg_no' => 'CRS002', 'course_code' => 'CS102', 'course_name' => 'Intro 2', 'term_code' => $term, 'last_seen_at' => now()]);

        // Assign Form
        $this->formManagement->assignToAllCourses($form, $term);

        // Check Pending
        $pending = $this->completionTracking->getPendingFormsForStudent('STU002', [['course_reg_no' => 'CRS002']], $term);
        $this->assertCount(1, $pending);
        $this->assertEquals($form->id, $pending->first()['form']->id);

        // Complete it
        $response = $this->responseSubmission->createOrResumeDraft($form, 'STU002', 'CRS002', $term);
        $this->responseSubmission->submitResponse($response, [$q1->id => 5]);

        // Check Pending Again (Should be empty)
        $pendingAfter = $this->completionTracking->getPendingFormsForStudent('STU002', [['course_reg_no' => 'CRS002']], $term);
        $this->assertCount(0, $pendingAfter);
    }
}
