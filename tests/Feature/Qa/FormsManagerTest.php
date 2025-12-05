<?php

namespace Tests\Feature\Qa;

use Tests\TestCase;
use App\Models\User;
use App\Models\Form;
use App\Models\FormCourseScope;
use App\Models\SisCourseRef;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected $qaUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qaUser = User::factory()->create(['role' => 'qa_officer']);
    }

    public function test_qa_can_access_forms_index()
    {
        $response = $this->actingAs($this->qaUser)->get('/qa/forms');

        $response->assertStatus(200);
        $response->assertViewIs('qa.forms.index');
    }

    public function test_qa_can_access_create_form_page()
    {
        $response = $this->actingAs($this->qaUser)->get('/qa/forms/create');

        $response->assertStatus(200);
        $response->assertViewIs('qa.forms.create');
    }

    public function test_course_form_creation_auto_creates_scopes()
    {
        // Seed some courses
        SisCourseRef::create([
            'course_reg_no' => 'REG-100',
            'course_code' => 'CS101',
            'course_name' => 'Intro to CS',
            'dept_name' => 'IT',
            'term_code' => '202410',
        ]);

        SisCourseRef::create([
            'course_reg_no' => 'REG-101',
            'course_code' => 'CS102',
            'course_name' => 'Data Structures',
            'dept_name' => 'IT',
            'term_code' => '202410',
        ]);

        $response = $this->actingAs($this->qaUser)->post('/qa/forms', [
            'title' => 'Test Course Feedback',
            'code' => 'TCF-2024',
            'form_type' => 'course_feedback',
            'term_code' => '202410',
        ]);

        $response->assertRedirect();

        // Verify form was created
        $form = Form::where('code', 'TCF-2024')->first();
        $this->assertNotNull($form);
        $this->assertEquals('course_feedback', $form->form_type);

        // Verify scopes were created for each course
        $scopes = FormCourseScope::where('form_id', $form->id)->get();
        $this->assertCount(2, $scopes);
        $this->assertTrue($scopes->contains('course_reg_no', 'REG-100'));
        $this->assertTrue($scopes->contains('course_reg_no', 'REG-101'));
    }

    public function test_service_form_creation_creates_single_global_scope()
    {
        $response = $this->actingAs($this->qaUser)->post('/qa/forms', [
            'title' => 'Test System Services',
            'code' => 'TSS-2024',
            'form_type' => 'system_services',
            'term_code' => '202410',
        ]);

        $response->assertRedirect();

        // Verify form was created
        $form = Form::where('code', 'TSS-2024')->first();
        $this->assertNotNull($form);
        $this->assertEquals('system_services', $form->form_type);

        // Verify single global scope was created with null course_reg_no
        $scopes = FormCourseScope::where('form_id', $form->id)->get();
        $this->assertCount(1, $scopes);
        $this->assertNull($scopes->first()->course_reg_no);
        $this->assertEquals('202410', $scopes->first()->term_code);
    }

    public function test_edit_button_routes_correctly()
    {
        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF-001',
            'form_type' => 'course_feedback',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->qaUser)->get('/qa/forms');

        $response->assertSee(route('qa.forms.edit', $form));
    }

    public function test_edit_page_loads_form_structure()
    {
        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF-002',
            'form_type' => 'course_feedback',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->qaUser)->get(route('qa.forms.edit', $form));

        $response->assertStatus(200);
        $response->assertViewIs('qa.forms.edit');
        $response->assertViewHas('form');
    }
}
