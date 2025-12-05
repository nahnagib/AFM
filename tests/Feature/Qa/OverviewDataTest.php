<?php

namespace Tests\Feature\Qa;

use Tests\TestCase;
use App\Models\User;
use App\Models\Form;
use App\Models\CompletionFlag;
use App\Models\SisCourseRef;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OverviewDataTest extends TestCase
{
    use RefreshDatabase;

    protected $qaUser;
    protected $termCode = '202410';

    protected function setUp(): void
    {
        parent::setUp();
        $this->qaUser = User::factory()->create(['role' => 'qa_officer']);
    }

    public function test_overview_page_loads_with_real_data()
    {
        // Seed test data
        $this->seedTestData();

        $response = $this->actingAs($this->qaUser)
            ->get('/qa');

        $response->assertStatus(200);
        $response->assertViewIs('qa.overview');
        $response->assertViewHas('metrics');
        $response->assertViewHas('participationChart');
        $response->assertViewHas('termCode');
    }

    public function test_kpi_calculations_are_correct()
    {
        // Create test data with known values
        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF001',
            'form_type' => 'course_feedback',
            'is_active' => true,
        ]);

        // Create course
        SisCourseRef::create([
            'course_reg_no' => 'CS101',
            'course_code' => 'CS101',
            'course_name' => 'Computer Science 101',
            'dept_name' => 'CS',
            'term_code' => $this->termCode,
        ]);

        // Create 10 completion flags: 7 completed, 3 pending
        // Use unique student IDs for each flag to avoid constraint violations
        for ($i = 1; $i <= 7; $i++) {
            CompletionFlag::create([
                'form_id' => $form->id,
                'sis_student_id' => 'S' . $i,
                'course_reg_no' => 'CS101',
                'term_code' => $this->termCode,
                'completed_at' => now(),
                'source' => 'system',
            ]);
        }

        for ($i = 8; $i <= 10; $i++) {
            CompletionFlag::create([
                'form_id' => $form->id,
                'sis_student_id' => 'S' . $i,
                'course_reg_no' => 'CS101',
                'term_code' => $this->termCode,
                'completed_at' => null,
                'source' => 'system',
            ]);
        }

        $response = $this->actingAs($this->qaUser)->get('/qa');

        $metrics = $response->viewData('metrics');

        // Total students: 10 unique (S1-S10)
        $this->assertEquals(10, $metrics['total_students']);

        // Participation rate: 7/10 = 70%
        $this->assertEquals(70.0, $metrics['participation_rate']);

        // Pending: 3
        $this->assertEquals(3, $metrics['pending_evaluations']);

        // High risk courses: 0 (70% > 60% threshold)
        $this->assertEquals(0, $metrics['high_risk_courses']);
    }

    public function test_participation_chart_includes_course_names()
    {
        $this->seedTestData();

        $response = $this->actingAs($this->qaUser)->get('/qa');

        $chart = $response->viewData('participationChart');

        $this->assertIsArray($chart);
        
        if (count($chart) > 0) {
            $this->assertArrayHasKey('course_reg_no', $chart[0]);
            $this->assertArrayHasKey('course_name', $chart[0]);
            $this->assertArrayHasKey('participation', $chart[0]);
        }
    }

    public function test_high_risk_courses_uses_configurable_threshold()
    {
        // Set threshold to 80%
        config(['afm.qa.high_risk_threshold' => 0.8]);

        $form = Form::create([
            'title' => 'Test Form',
            'code' => 'TF001',
            'form_type' => 'course_feedback',
            'is_active' => true,
        ]);

        SisCourseRef::create([
            'course_reg_no' => 'CS101',
            'course_code' => 'CS101',
            'course_name' => 'Computer Science 101',
            'dept_name' => 'CS',
            'term_code' => $this->termCode,
        ]);

        // Create course with 70% completion (below 80% threshold)
        for ($i = 1; $i <= 7; $i++) {
            CompletionFlag::create([
                'form_id' => $form->id,
                'sis_student_id' => 'S' . $i,
                'course_reg_no' => 'CS101',
                'term_code' => $this->termCode,
                'completed_at' => now(),
                'source' => 'system',
            ]);
        }

        for ($i = 8; $i <= 10; $i++) {
            CompletionFlag::create([
                'form_id' => $form->id,
                'sis_student_id' => 'S' . $i,
                'course_reg_no' => 'CS101',
                'term_code' => $this->termCode,
                'completed_at' => null,
                'source' => 'system',
            ]);
        }

        $response = $this->actingAs($this->qaUser)->get('/qa');
        $metrics = $response->viewData('metrics');

        // Should be 1 high-risk course (70% < 80%)
        $this->assertEquals(1, $metrics['high_risk_courses']);
    }

    protected function seedTestData()
    {
        $form = Form::create([
            'title' => 'Course Feedback',
            'code' => 'CF001',
            'form_type' => 'course_feedback',
            'is_active' => true,
        ]);

        // Create multiple courses
        $courses = ['CS101', 'CS102', 'MATH201'];
        foreach ($courses as $idx => $courseCode) {
            SisCourseRef::create([
                'course_reg_no' => $courseCode,
                'course_code' => $courseCode,
                'course_name' => $courseCode . ' Course',
                'dept_name' => 'DEPT',
                'term_code' => $this->termCode,
            ]);

            // Create some completion flags with unique students per course
            for ($i = 1; $i <= 5; $i++) {
                CompletionFlag::create([
                    'form_id' => $form->id,
                    'sis_student_id' => 'S' . (($idx * 10) + $i), // Unique per course
                    'course_reg_no' => $courseCode,
                    'term_code' => $this->termCode,
                    'completed_at' => $i <= 3 ? now() : null,
                    'source' => 'system',
                ]);
            }
        }
    }
}
