<?php

namespace Tests\Feature\Sso;

use Tests\TestCase;
use App\Models\AfmSessionToken;
use App\Services\SsoTokenIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class QaSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_qa_payload_validation_succeeds()
    {
        $payload = $this->buildQaPayload();
        
        $verifier = app(\App\Services\JsonPayloadVerifier::class);
        $result = $verifier->verify($payload);
        
        $this->assertTrue($result['valid']);
    }

    public function test_qa_token_creation()
    {
        $payload = $this->buildQaPayload();
        
        $intake = app(SsoTokenIntakeService::class);
        $request = \Illuminate\Http\Request::create('/api/sso/token', 'POST', $payload);
        
        $result = $intake->handle($payload, $request);
        
        $this->assertEquals('success', $result['status']);
        $this->assertNotNull($result['token_id']);
        $this->assertStringContainsString('/sso/handshake/', $result['redirect_to']);
        
        // Verify token in database
        $token = AfmSessionToken::find($result['token_id']);
        $this->assertNotNull($token);
        $this->assertEquals('qa_officer', $token->role);
        $this->assertEquals('U9021', $token->sis_student_id);
        $this->assertEmpty($token->courses_json);
    }

    public function test_qa_handshake_redirects_to_qa_dashboard()
    {
        // Create a valid QA token
        $payload = $this->buildQaPayload();
        $intake = app(SsoTokenIntakeService::class);
        $request = \Illuminate\Http\Request::create('/api/sso/token', 'POST', $payload);
        $result = $intake->handle($payload, $request);
        
        $tokenId = $result['token_id'];
        
        // Test handshake
        $response = $this->get("/sso/handshake/{$tokenId}");
        
        $response->assertRedirect('/qa');
        
        // Verify session
        $this->assertEquals('qa_officer', session('afm_role'));
        $this->assertEquals($tokenId, session('afm_token_id'));
    }

    public function test_qa_can_access_dashboard_after_sso()
    {
        // Create and consume token
        $payload = $this->buildQaPayload();
        $intake = app(SsoTokenIntakeService::class);
        $request = \Illuminate\Http\Request::create('/api/sso/token', 'POST', $payload);
        $result = $intake->handle($payload, $request);
        
        // Handshake
        $this->get("/sso/handshake/{$result['token_id']}");
        
        // Try to access QA dashboard
        $response = $this->get('/qa');
        
        $response->assertStatus(200);
        $response->assertViewIs('qa.overview');
    }

    public function test_student_cannot_access_qa_dashboard()
    {
        // Create student token
        $payload = $this->buildStudentPayload();
        $intake = app(SsoTokenIntakeService::class);
        $request = \Illuminate\Http\Request::create('/api/sso/token', 'POST', $payload);
        $result = $intake->handle($payload, $request);
        
        // Handshake as student
        $this->get("/sso/handshake/{$result['token_id']}");
        
        // Try to access QA dashboard
        $response = $this->get('/qa');
        
        $response->assertStatus(403); // Forbidden
    }

    protected function buildQaPayload(): array
    {
        $payload = [
            'iss' => config('afm_sso.iss'),
            'aud' => config('afm_sso.aud'),
            'v' => config('afm_sso.version'),
            'request_id' => (string) Str::uuid(),
            'role' => 'qa_officer',
            'user_id' => 'U9021',
            'user_name' => 'QA Officer Test',
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(5)->timestamp,
            'nonce' => Str::random(32),
            'sig_alg' => 'HMAC-SHA256',
        ];

        $secret = config('afm_sso.shared_secret');
        $canonicalJson = \App\Support\AfmJsonCanonicalizer::canonicalize($payload);
        $signature = hash_hmac('sha256', $canonicalJson, $secret);
        $payload['signature'] = $signature;

        return $payload;
    }

    protected function buildStudentPayload(): array
    {
        $payload = [
            'iss' => config('afm_sso.iss'),
            'aud' => config('afm_sso.aud'),
            'v' => config('afm_sso.version'),
            'request_id' => (string) Str::uuid(),
            'role' => 'student',
            'student_id' => 'S12345',
            'student_name' => 'Test Student',
            'student_number' => 'S12345',
            'courses' => [],
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(5)->timestamp,
            'nonce' => Str::random(32),
            'sig_alg' => 'HMAC-SHA256',
        ];

        $secret = config('afm_sso.shared_secret');
        $canonicalJson = \App\Support\AfmJsonCanonicalizer::canonicalize($payload);
        $signature = hash_hmac('sha256', $canonicalJson, $secret);
        $payload['signature'] = $signature;

        return $payload;
    }
}
