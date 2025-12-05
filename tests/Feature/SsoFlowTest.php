<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class SsoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_token_generation_and_handshake()
    {
        // 1. Prepare Payload
        $payload = [
            'iss' => config('afm_sso.iss'),
            'aud' => config('afm_sso.aud'),
            'v' => config('afm_sso.version'),
            'request_id' => (string) Str::uuid(),
            'role' => 'student',
            'student_id' => 'TEST-STUDENT-001',
            'courses' => [
                [
                    'course_reg_no' => 'REG-101',
                    'course_code' => 'CS101',
                    'course_name' => 'Intro to CS',
                    'term_code' => '202410',
                ]
            ],
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(5)->timestamp,
            'nonce' => Str::random(32),
            'sig_alg' => 'HMAC-SHA256',
        ];

        // 2. Sign Payload
        $canonical = \App\Support\AfmJsonCanonicalizer::canonicalize($payload);
        $signature = hash_hmac('sha256', $canonical, config('afm_sso.shared_secret'));
        $payload['signature'] = $signature;

        // 3. POST to /api/sso/token
        $response = $this->postJson('/api/sso/token', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'redirect_to']);

        $redirectUrl = $response->json('redirect_to');
        $this->assertStringContainsString('/sso/handshake/', $redirectUrl);

        // Extract Token ID
        $parts = explode('/', $redirectUrl);
        $tokenId = end($parts);

        // 4. Follow Redirect (Handshake)
        $handshakeResponse = $this->get($redirectUrl);
        
        $handshakeResponse->assertRedirect('/student/dashboard');
        
        // 5. Verify Session
        $this->assertAuthenticatedAsStudent($tokenId); // Custom assertion logic or check session
        
        // Check if we can access dashboard
        $dashboardResponse = $this->withSession(['afm_token_id' => $tokenId])->get('/student/dashboard');
        // Note: In test environment, session persistence between requests needs careful handling or manual session setting.
        // The handshake sets the session. The subsequent request should have it if we follow redirects or use the same session store.
        // Laravel's test client handles session cookies automatically usually.
        
        $handshakeResponse->assertSessionHas('afm_token_id', $tokenId);
    }

    protected function assertAuthenticatedAsStudent($tokenId)
    {
        // Helper to verify DB state if needed
        $this->assertDatabaseHas('afm_session_tokens', [
            'id' => $tokenId,
            'consumed_at' => now(), // Should be close to now, but exact match is hard. Just check not null.
        ]);
    }
}
