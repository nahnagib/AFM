<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AfmFormTemplate;
use App\Models\Response;
use App\Models\AfmSessionToken;

class StudentFeedbackContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed or create necessary data
        // We need a course form template
        $this->courseTemplate = AfmFormTemplate::create([
            'title' => 'Course Feedback',
            'code' => 'COURSE-TEST',
            'form_type' => 'course',
            'schema_json' => ['sections' => []],
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }

    public function test_start_response_creates_record_with_valid_context()
    {
        // Mock SSO Token
        $token = AfmSessionToken::create([
            'request_id' => 'req_123',
            'nonce' => 'nonce_123',
            'payload_hash' => 'hash',
            'sis_student_id' => '2024001',
            'courses_json' => [['course_reg_no' => 'REG-100', 'term_code' => '202410']],
            'role' => 'student',
            'issued_at' => now(),
            'expires_at' => now()->addHour(),
            'client_ip' => '127.0.0.1',
            'user_agent' => 'TestAgent',
        ]);

        // Simulate request with valid course context
        $response = $this->withMiddleware(['auth.afm', 'role:student'])
            ->withSession(['afm_token_id' => $token->id]) // Assuming middleware uses session
            ->get("/student/forms/{$this->courseTemplate->id}/respond?course=REG-100&term=202410");

        $response->assertStatus(200);
        $response->assertViewIs('student.feedback.show');

        // Verify DB record
        $this->assertDatabaseHas('responses', [
            'form_template_id' => $this->courseTemplate->id,
            'sis_student_id' => '2024001',
            'course_reg_no' => 'REG-100',
            'term_code' => '202410',
        ]);
    }

    public function test_start_response_fails_without_course_context_for_course_form()
    {
        // Mock SSO Token
        $token = AfmSessionToken::create([
            'request_id' => 'req_456',
            'nonce' => 'nonce_456',
            'payload_hash' => 'hash',
            'sis_student_id' => '2024002',
            'courses_json' => [['course_reg_no' => 'REG-100', 'term_code' => '202410']],
            'role' => 'student',
            'issued_at' => now(),
            'expires_at' => now()->addHour(),
            'client_ip' => '127.0.0.1',
            'user_agent' => 'TestAgent',
        ]);

        // Simulate request WITHOUT course context (or with empty/system)
        // Case 1: No course param
        $response = $this->withMiddleware(['auth.afm', 'role:student'])
            ->withSession(['afm_token_id' => $token->id])
            ->get("/student/forms/{$this->courseTemplate->id}/respond?term=202410");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'invalid_course_context',
        ]);

        // Case 2: course=system for a course form
        $response2 = $this->withMiddleware(['auth.afm', 'role:student'])
            ->withSession(['afm_token_id' => $token->id])
            ->get("/student/forms/{$this->courseTemplate->id}/respond?course=system&term=202410");

        $response2->assertStatus(400);
        
        // Verify NO DB record created
        $this->assertDatabaseMissing('responses', [
            'form_template_id' => $this->courseTemplate->id,
            'sis_student_id' => '2024002',
        ]);
    }
}
