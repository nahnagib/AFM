<?php

namespace App\Services;

use Exception;

class JsonPayloadVerifier
{
    private string $sharedSecret;
    private string $expectedIss;
    private string $expectedAud;
    private string $expectedVersion;

    public function __construct()
    {
        $this->sharedSecret = config('afm_sso.shared_secret');
        $this->expectedIss = config('afm_sso.iss');
        $this->expectedAud = config('afm_sso.aud');
        $this->expectedVersion = config('afm_sso.version');
    }

    /**
     * Verify the JSON payload signature and metadata
     *
     * @param array $payload
     * @return array Validation result with 'valid' boolean and 'error' string if invalid
     */
    public function verify(array $payload): array
    {
        // Check required fields - different for student vs QA
        $commonFields = ['iss', 'aud', 'v', 'request_id', 'role', 'issued_at', 'expires_at', 'nonce', 'sig_alg', 'signature'];
        
        foreach ($commonFields as $field) {
            if (!isset($payload[$field])) {
                return ['valid' => false, 'error' => "Missing required field: {$field}"];
            }
        }
        
        // Role-specific required fields
        if ($payload['role'] === 'student') {
            if (!isset($payload['student_id'])) {
                return ['valid' => false, 'error' => 'Missing required field: student_id'];
            }
            if (!isset($payload['courses'])) {
                return ['valid' => false, 'error' => 'Missing required field: courses'];
            }
        } elseif ($payload['role'] === 'qa_officer') {
            if (!isset($payload['user_id'])) {
                return ['valid' => false, 'error' => 'Missing required field: user_id'];
            }
        }

        // Verify issuer
        if ($payload['iss'] !== $this->expectedIss) {
            return ['valid' => false, 'error' => 'Invalid issuer'];
        }

        // Verify audience
        if ($payload['aud'] !== $this->expectedAud) {
            return ['valid' => false, 'error' => 'Invalid audience'];
        }

        // Verify version
        if ($payload['v'] !== $this->expectedVersion) {
            return ['valid' => false, 'error' => 'Invalid version'];
        }

        // Verify signature algorithm
        $alg = $payload['sig_alg'] ?? null;
        $supportedAlgs = [
            'sha256' => 'sha256',
            'HMAC-SHA256' => 'sha256',
            'HS256' => 'sha256',
        ];

        if (!isset($supportedAlgs[$alg])) {
            return ['valid' => false, 'error' => "Unsupported signature algorithm: {$alg}"];
        }
        
        $phpAlgo = $supportedAlgs[$alg];

        // Verify role
        $allowedRoles = config('afm_sso.allowed_roles');
        if (!in_array($payload['role'], $allowedRoles)) {
            return ['valid' => false, 'error' => 'Invalid role'];
        }

        // Verify timestamps
        $issuedAt = is_numeric($payload['issued_at']) ? (int)$payload['issued_at'] : strtotime($payload['issued_at']);
        $expiresAt = is_numeric($payload['expires_at']) ? (int)$payload['expires_at'] : strtotime($payload['expires_at']);
        $now = time();

        if ($issuedAt > $now + 300) { // Allow 5 minutes clock skew
            return ['valid' => false, 'error' => 'Token issued in the future'];
        }

        if ($expiresAt < $now) {
            return ['valid' => false, 'error' => 'Token expired'];
        }

        // Verify signature
        $receivedSignature = $payload['signature'];
        $canonicalPayload = $this->buildCanonicalPayload($payload);
        $expectedSignature = hash_hmac($phpAlgo, $canonicalPayload, $this->sharedSecret);

        if (!hash_equals($expectedSignature, $receivedSignature)) {
            return ['valid' => false, 'error' => 'Invalid signature'];
        }

        return ['valid' => true];
    }

    /**
     * Build canonical JSON payload for signature verification
     * Excludes the 'signature' field and maintains consistent key ordering
     *
     * @param array $payload
     * @return string
     */
    private function buildCanonicalPayload(array $payload): string
    {
        return \App\Support\AfmJsonCanonicalizer::canonicalize($payload);
    }

    /**
     * Compute payload hash for audit logging
     *
     * @param array $payload
     * @return string
     */
    public function computePayloadHash(array $payload): string
    {
        $canonical = $this->buildCanonicalPayload($payload);
        return hash('sha256', $canonical);
    }
}
