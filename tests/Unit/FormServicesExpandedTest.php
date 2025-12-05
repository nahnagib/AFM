<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use App\Services\FormManagementService;
use App\Services\FormBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormServicesExpandedTest extends TestCase
{
    use RefreshDatabase;

    protected $formManagement;
    protected $formBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formManagement = new FormManagementService();
        $this->formBuilder = new FormBuilderService();
    }

    public function test_cannot_publish_form_without_sections()
    {
        $form = $this->formManagement->createForm(['code' => 'NO_SECTIONS', 'title' => 'No Sections', 'form_type' => 'course_feedback']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot publish form without sections');

        $this->formManagement->publishForm($form);
    }

    public function test_cannot_publish_form_without_questions()
    {
        $form = $this->formManagement->createForm(['code' => 'NO_QUESTIONS', 'title' => 'No Questions', 'form_type' => 'course_feedback']);
        $this->formBuilder->addSection($form, ['title' => 'Section 1']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot publish form without questions');

        $this->formManagement->publishForm($form);
    }

    public function test_duplicate_form_copies_structure()
    {
        $original = $this->formManagement->createForm(['code' => 'ORIGINAL', 'title' => 'Original', 'form_type' => 'course_feedback']);
        $section = $this->formBuilder->addSection($original, ['title' => 'Section 1']);
        $question = $this->formBuilder->addQuestion($section, ['prompt' => 'Q1', 'qtype' => 'text', 'order' => 1]);

        $duplicate = $this->formManagement->duplicateForm($original, 'DUPLICATE', 'Duplicate Form');

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertEquals('DUPLICATE', $duplicate->code);
        $this->assertCount(1, $duplicate->sections);
        $this->assertCount(1, $duplicate->questions);
        $this->assertEquals('Section 1', $duplicate->sections->first()->title);
        $this->assertEquals('Q1', $duplicate->questions->first()->prompt);
    }

    public function test_archive_form_sets_inactive()
    {
        $form = $this->formManagement->createForm(['code' => 'ARCHIVE_TEST', 'title' => 'Archive Test', 'form_type' => 'course_feedback']);
        $section = $this->formBuilder->addSection($form, ['title' => 'S1']);
        $this->formBuilder->addQuestion($section, ['prompt' => 'Q1', 'qtype' => 'text', 'order' => 1]);
        $this->formManagement->publishForm($form);

        $this->assertTrue($form->fresh()->is_active);

        $this->formManagement->archiveForm($form);

        $this->assertFalse($form->fresh()->is_active);
    }

    public function test_add_section_auto_increments_order()
    {
        $form = $this->formManagement->createForm(['code' => 'ORDER_TEST', 'title' => 'Order Test', 'form_type' => 'course_feedback']);
        
        $section1 = $this->formBuilder->addSection($form, ['title' => 'Section 1']);
        $section2 = $this->formBuilder->addSection($form, ['title' => 'Section 2']);

        $this->assertEquals(1, $section1->order);
        $this->assertEquals(2, $section2->order);
    }

    public function test_add_question_auto_increments_order()
    {
        $form = $this->formManagement->createForm(['code' => 'Q_ORDER_TEST', 'title' => 'Q Order Test', 'form_type' => 'course_feedback']);
        $section = $this->formBuilder->addSection($form, ['title' => 'Section 1']);
        
        $q1 = $this->formBuilder->addQuestion($section, ['prompt' => 'Q1', 'qtype' => 'text', 'order' => 1]);
        $q2 = $this->formBuilder->addQuestion($section, ['prompt' => 'Q2', 'qtype' => 'text']);

        $this->assertEquals(1, $q1->order);
        $this->assertEquals(2, $q2->order);
    }

    public function test_cannot_edit_published_form_with_responses()
    {
        $form = $this->formManagement->createForm(['code' => 'LOCKED', 'title' => 'Locked', 'form_type' => 'course_feedback']);
        $section = $this->formBuilder->addSection($form, ['title' => 'S1']);
        $this->formBuilder->addQuestion($section, ['prompt' => 'Q1', 'qtype' => 'text', 'order' => 1]);
        $this->formManagement->publishForm($form);

        // Simulate response
        \App\Models\Response::create([
            'form_id' => $form->id,
            'sis_student_id' => 'STU001',
            'course_reg_no' => 'CRS001',
            'term_code' => '202410',
            'status' => 'submitted',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot edit form structure after it has responses');

        $this->formBuilder->addSection($form, ['title' => 'New Section']);
    }
}
