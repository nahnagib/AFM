<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use App\Models\Response;
use App\Models\SisStudent;
use App\Models\SisCourseRef;
use App\Services\ResponseSubmissionService;
use App\Services\FormManagementService;
use App\Services\FormBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class ResponseSubmissionExpandedTest extends TestCase
{
    use RefreshDatabase;

    protected $responseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseService = new ResponseSubmissionService();
    }

    public function test_mcq_multi_creates_multiple_response_items()
    {
        $form = $this->createFormWithQuestion('mcq_multi');
        $response = $this->responseService->createOrResumeDraft($form, 'STU001', 'CRS001', '202410');

        $question = $form->questions->first();
        $answers = [$question->id => ['option1', 'option2', 'option3']];

        $this->responseService->saveDraft($response, $answers);

        $items = $response->fresh()->items;
        $this->assertCount(3, $items);
        $this->assertEquals('option1', $items[0]->option_value);
        $this->assertEquals('option2', $items[1]->option_value);
        $this->assertEquals('option3', $items[2]->option_value);
    }

    public function test_submit_validates_required_questions()
    {
        $form = $this->createFormWithRequiredQuestion();
        $response = $this->responseService->createOrResumeDraft($form, 'STU001', 'CRS001', '202410');

        $this->expectException(ValidationException::class);

        // Submit without answering required question
        $this->responseService->submitResponse($response, []);
    }

    public function test_submit_validates_likert_range()
    {
        $form = $this->createFormWithQuestion('likert');
        $response = $this->responseService->createOrResumeDraft($form, 'STU001', 'CRS001', '202410');

        $question = $form->questions->first();

        // Save out-of-range value
        $this->responseService->saveDraft($response, [$question->id => 10]);

        $this->expectException(ValidationException::class);
        $this->responseService->submitResponse($response, [$question->id => 10]);
    }

    public function test_cannot_edit_submitted_response()
    {
        $form = $this->createFormWithQuestion('text');
        $response = $this->responseService->createOrResumeDraft($form, 'STU001', 'CRS001', '202410');

        $question = $form->questions->first();
        $this->responseService->submitResponse($response, [$question->id => 'Answer']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot edit a submitted response');

        $this->responseService->saveDraft($response, [$question->id => 'New Answer']);
    }

    public function test_cannot_submit_already_submitted_response()
    {
        $form = $this->createFormWithQuestion('text');
        $response = $this->responseService->createOrResumeDraft($form, 'STU001', 'CRS001', '202410');

        $question = $form->questions->first();
        $this->responseService->submitResponse($response, [$question->id => 'Answer']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Response is already submitted');

        $this->responseService->submitResponse($response, [$question->id => 'Answer']);
    }

    public function test_submission_creates_completion_flag()
    {
        $form = $this->createFormWithQuestion('text');
        $response = $this->responseService->createOrResumeDraft($form, 'STU001', 'CRS001', '202410');

        $question = $form->questions->first();
        $this->responseService->submitResponse($response, [$question->id => 'Answer']);

        $this->assertDatabaseHas('completion_flags', [
            'form_id' => $form->id,
            'sis_student_id' => 'STU001',
            'course_reg_no' => 'CRS001',
            'term_code' => '202410',
        ]);
    }

    // Helper methods
    protected function createFormWithQuestion($qtype, $required = true)
    {
        $mgmt = new FormManagementService();
        $builder = new FormBuilderService();

        $form = $mgmt->createForm(['code' => 'TEST', 'title' => 'Test', 'form_type' => 'course_feedback']);
        $section = $builder->addSection($form, ['title' => 'Section 1']);
        $builder->addQuestion($section, [
            'prompt' => 'Question 1',
            'qtype' => $qtype,
            'required' => $required,
            'scale_min' => 1,
            'scale_max' => 5,
            'order' => 1,
        ]);

        $mgmt->publishForm($form);
        return $form->fresh();
    }

    protected function createFormWithRequiredQuestion()
    {
        return $this->createFormWithQuestion('text', true);
    }
}
