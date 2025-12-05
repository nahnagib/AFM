<?php

namespace Tests\Feature\Qa;

use Tests\TestCase;
use App\Models\User;
use App\Models\CompletionFlag;
use App\Models\FormCourseScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles if necessary or mock middleware
        // Assuming middleware checks for 'qa' role
    }

    public function test_qa_can_view_overview_page()
    {
        $qaUser = User::factory()->create(['role' => 'qa_officer']);

        $response = $this->actingAs($qaUser)
            ->get(route('qa.overview'));

        $response->assertStatus(200);
        $response->assertViewIs('qa.overview');
        $response->assertViewHas('metrics');
        $response->assertViewHas('participationChart');
    }

    public function test_kpi_stats_calculation()
    {
        $qaUser = User::factory()->create(['role' => 'qa_officer']);
        $termCode = '202410';

        // Create Form
        $form = \App\Models\Form::create([
            'title' => 'Test Form',
            'code' => 'TEST001',
            'form_type' => 'course_feedback',
            'is_active' => true,
        ]);

        // Seed data
        CompletionFlag::create([
            'form_id' => $form->id,
            'course_reg_no' => 'CS101',
            'term_code' => $termCode,
            'sis_student_id' => 'S1',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        CompletionFlag::create([
            'form_id' => $form->id,
            'course_reg_no' => 'CS101',
            'term_code' => $termCode,
            'sis_student_id' => 'S2',
            'status' => 'in_progress',
        ]);

        // Mock scope count (participation rate denominator logic in service uses total flags)
        
        $response = $this->actingAs($qaUser)
            ->get(route('qa.overview'));

        $metrics = $response->viewData('metrics');
        
        // Total students: S1 and S2 -> 2
        $this->assertEquals(2, $metrics['total_students']);
        
        // Participation: 1 completed / 2 total = 50%
        $this->assertEquals(50.0, $metrics['participation_rate']);
        
        // Pending: 1 (S2)
        $this->assertEquals(1, $metrics['pending_evaluations']);
    }

    public function test_run_alerts_redirects_back()
    {
        $qaUser = User::factory()->create(['role' => 'qa_officer']);

        // Since we changed it to a button that does nothing (no form submission), 
        // there is no route to hit for "Run Alerts" anymore in the UI, 
        // but the route definition might still exist in web.php.
        // Let's check if we removed the route. We didn't remove the route in web.php, just the form in blade.
        // So we can still test the controller method via direct POST.

        $response = $this->actingAs($qaUser)
            ->post(route('qa.run-alerts'));

        $response->assertRedirect(route('qa.overview'));
        $response->assertSessionHas('info', 'Alerts feature is coming soon.');
    }
}
