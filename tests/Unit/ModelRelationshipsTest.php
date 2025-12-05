<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\ResponseItem;
use App\Models\CompletionFlag;
use App\Models\SisStudent;
use App\Models\SisCourseRef;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_has_sections_relationship()
    {
        $form = Form::create(['code' => 'TEST', 'title' => 'Test', 'form_type' => 'course_feedback']);
        $section = FormSection::create(['form_id' => $form->id, 'title' => 'Section 1', 'order' => 1]);

        $this->assertCount(1, $form->sections);
        $this->assertEquals('Section 1', $form->sections->first()->title);
    }

    public function test_form_has_questions_through_sections()
    {
        $form = Form::create(['code' => 'TEST', 'title' => 'Test', 'form_type' => 'course_feedback']);
        $section = FormSection::create(['form_id' => $form->id, 'title' => 'Section 1', 'order' => 1]);
        $question = Question::create([
            'section_id' => $section->id,
            'prompt' => 'Q1',
            'qtype' => 'text',
            'order' => 1,
        ]);

        $this->assertCount(1, $form->questions);
        $this->assertEquals('Q1', $form->questions->first()->prompt);
    }

    public function test_question_has_options_relationship()
    {
        $section = FormSection::create(['form_id' => 1, 'title' => 'S1', 'order' => 1]);
        $question = Question::create(['section_id' => $section->id, 'prompt' => 'Q1', 'qtype' => 'mcq_single', 'order' => 1]);
        
        QuestionOption::create(['question_id' => $question->id, 'label' => 'Option 1', 'value' => '1', 'order' => 1]);
        QuestionOption::create(['question_id' => $question->id, 'label' => 'Option 2', 'value' => '2', 'order' => 2]);

        $this->assertCount(2, $question->options);
    }

    public function test_response_has_items_relationship()
    {
        $response = Response::create([
            'form_id' => 1,
            'sis_student_id' => 'STU001',
            'course_reg_no' => 'CRS001',
            'term_code' => '202410',
            'status' => 'draft',
        ]);

        ResponseItem::create(['response_id' => $response->id, 'question_id' => 1, 'text_value' => 'Answer']);

        $this->assertCount(1, $response->items);
        $this->assertEquals('Answer', $response->items->first()->text_value);
    }

    public function test_response_computes_student_hash_on_create()
    {
        $response = Response::create([
            'form_id' => 1,
            'sis_student_id' => 'STU001',
            'course_reg_no' => 'CRS001',
            'term_code' => '202410',
        ]);

        $this->assertNotNull($response->student_hash);
        $this->assertEquals(Response::computeStudentHash('STU001'), $response->student_hash);
    }

    public function test_form_scope_published()
    {
        Form::create(['code' => 'PUBLISHED', 'title' => 'Published', 'form_type' => 'course_feedback', 'is_published' => true]);
        Form::create(['code' => 'DRAFT', 'title' => 'Draft', 'form_type' => 'course_feedback', 'is_published' => false]);

        $published = Form::published()->get();

        $this->assertCount(1, $published);
        $this->assertEquals('PUBLISHED', $published->first()->code);
    }

    public function test_form_scope_active()
    {
        Form::create(['code' => 'ACTIVE', 'title' => 'Active', 'form_type' => 'course_feedback', 'is_active' => true]);
        Form::create(['code' => 'INACTIVE', 'title' => 'Inactive', 'form_type' => 'course_feedback', 'is_active' => false]);

        $active = Form::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('ACTIVE', $active->first()->code);
    }

    public function test_response_scope_submitted()
    {
        Response::create(['form_id' => 1, 'sis_student_id' => 'S1', 'course_reg_no' => 'C1', 'term_code' => '202410', 'status' => 'submitted']);
        Response::create(['form_id' => 1, 'sis_student_id' => 'S2', 'course_reg_no' => 'C1', 'term_code' => '202410', 'status' => 'draft']);

        $submitted = Response::submitted()->get();

        $this->assertCount(1, $submitted);
        $this->assertEquals('submitted', $submitted->first()->status);
    }

    public function test_completion_flag_mark_complete()
    {
        $flag = CompletionFlag::markComplete(1, 'STU001', 'CRS001', '202410', 'student');

        $this->assertDatabaseHas('completion_flags', [
            'form_id' => 1,
            'sis_student_id' => 'STU001',
            'course_reg_no' => 'CRS001',
            'term_code' => '202410',
        ]);

        $this->assertNotNull($flag->completed_at);
    }

    public function test_response_item_value_accessor()
    {
        $response = Response::create(['form_id' => 1, 'sis_student_id' => 'S1', 'course_reg_no' => 'C1', 'term_code' => '202410']);
        
        $numericItem = ResponseItem::create(['response_id' => $response->id, 'question_id' => 1, 'numeric_value' => 5]);
        $textItem = ResponseItem::create(['response_id' => $response->id, 'question_id' => 2, 'text_value' => 'Text answer']);
        $optionItem = ResponseItem::create(['response_id' => $response->id, 'question_id' => 3, 'option_value' => 'yes']);

        $this->assertEquals(5, $numericItem->value);
        $this->assertEquals('Text answer', $textItem->value);
        $this->assertEquals('yes', $optionItem->value);
    }
}
