<?php

namespace Tests\Feature\Qa;

use Tests\TestCase;
use App\Models\User;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected $qaUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qaUser = User::factory()->create(['role' => 'qa_officer']);
    }

    public function test_qa_can_create_form()
    {
        $response = $this->actingAs($this->qaUser)
            ->post(route('qa.forms.store'), [
                'title' => 'Test Course Feedback',
                'code' => 'TCF001',
                'form_type' => 'course_feedback',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('forms', [
            'title' => 'Test Course Feedback',
            'code' => 'TCF001',
            'form_type' => 'course_feedback',
            'is_active' => false,
        ]);
    }

    public function test_qa_can_add_section_to_form()
    {
        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF001',
            'form_type' => 'course_feedback',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->qaUser)
            ->postJson(route('qa.forms.sections.store', $form), [
                'title' => 'Course Content',
                'order' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('form_sections', [
            'form_id' => $form->id,
            'title' => 'Course Content',
            'order' => 1,
        ]);
    }

    public function test_qa_can_add_question_to_section()
    {
        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF001',
            'form_type' => 'course_feedback',
            'is_active' => false,
        ]);

        $section = FormSection::create([
            'form_id' => $form->id,
            'title' => 'Test Section',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->qaUser)
            ->postJson(route('qa.sections.questions.store', $section), [
                'text' => 'How would you rate the course?',
                'qtype' => 'likert',
                'is_required' => true,
                'scale_min' => 1,
                'scale_max' => 5,
                'order' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('questions', [
            'form_section_id' => $section->id,
            'text' => 'How would you rate the course?',
            'qtype' => 'likert',
        ]);
    }

    public function test_cannot_delete_section_with_questions()
    {
        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF001',
            'form_type' => 'course_feedback',
            'is_active' => false,
        ]);

        $section = FormSection::create([
            'form_id' => $form->id,
            'title' => 'Test Section',
            'order' => 1,
        ]);

        Question::create([
            'form_section_id' => $section->id,
            'code' => 'Q1',
            'text' => 'Test Question',
            'qtype' => 'text',
            'is_required' => false,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->qaUser)
            ->deleteJson(route('qa.sections.destroy', $section));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_qa_can_activate_form()
    {
        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF001',
            'form_type' => 'course_feedback',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->qaUser)
            ->putJson(route('qa.forms.update', $form), [
                'is_active' => true,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'is_active' => true,
        ]);
    }
}
