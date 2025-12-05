<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Form;
use App\Services\FormManagementService;
use App\Services\FormBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QAFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SisDataSeeder::class);
    }

    public function test_qa_officer_can_access_overview()
    {
        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'qa_officer',
            'afm_user_id' => 'qa001',
        ]);

        $response = $this->get('/qa');

        $response->assertStatus(200);
        $response->assertViewIs('qa.overview');
    }

    public function test_qa_officer_can_publish_form()
    {
        $formManagement = new FormManagementService();
        $formBuilder = new FormBuilderService();

        $form = $formManagement->createForm([
            'code' => 'QA_TEST',
            'title' => 'QA Test Form',
            'form_type' => 'course_feedback',
        ]);

        $section = $formBuilder->addSection($form, ['title' => 'Test Section']);
        $question = $formBuilder->addQuestion($section, [
            'prompt' => 'Test Question',
            'qtype' => 'text',
            'required' => true,
        ]);

        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'qa_officer',
            'afm_user_id' => 'qa001',
        ]);

        $response = $this->post("/qa/forms/{$form->id}/publish");

        $response->assertRedirect();
        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'is_published' => true,
        ]);
    }

    public function test_qa_officer_can_view_reports()
    {
        session([
            'afm_token_id' => 'test-token',
            'afm_role' => 'qa_officer',
            'afm_user_id' => 'qa001',
        ]);

        $response = $this->get('/qa/reports/completion?term=202410');

        $response->assertStatus(200);
        $response->assertViewIs('qa.reports.completion');
    }
}
