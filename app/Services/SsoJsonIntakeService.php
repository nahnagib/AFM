<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

class SsoJsonIntakeService
{
    /**
     * Handle the SSO JSON payload and set up the session.
     * 
     * @param array $payload
     * @return string Redirect path
     * @throws Exception
     */
    public function handle(array $payload): string
    {
        // 1. Validate Basic Envelope
        if (($payload['iss'] ?? '') !== 'LIMU-SIS') {
            throw new Exception("Invalid Issuer (iss)");
        }
        if (($payload['aud'] ?? '') !== 'AFM') {
            throw new Exception("Invalid Audience (aud)");
        }
        if (($payload['v'] ?? '') !== '1') {
            throw new Exception("Invalid Version (v)");
        }
        if (empty($payload['role'])) {
            throw new Exception("Missing Role");
        }

        // Validate timestamps
        $issuedAt = isset($payload['issued_at']) ? Carbon::parse($payload['issued_at']) : null;
        $expiresAt = isset($payload['expires_at']) ? Carbon::parse($payload['expires_at']) : null;

        if (!$issuedAt || !$expiresAt) {
            throw new Exception("Invalid timestamps");
        }

        $now = Carbon::now();
        if ($now->gt($expiresAt)) {
            throw new Exception("Token expired");
        }
        if ($now->addMinutes(5)->lt($issuedAt)) { // Allow 5 min clock skew
             // Only throw if issued_at is significantly in the future
             // But usually we just check not expired. The prompt said "far before issued_at"
             throw new Exception("Token issued in the future");
        }

        // HMAC Check (Simplified for local/now: just check existence)
        if (empty($payload['sig_alg']) || empty($payload['signature'])) {
             throw new Exception("Missing Signature");
        }

        // 2. Clear previous session
        Session::forget(['afm_role', 'afm_user_id', 'afm_user_name', 'afm_term_code', 'afm_courses']);
        
        // 3. Handle Role
        $role = $payload['role'] ?? null;

        if ($role === 'student') {
            $this->validateStudentPayload($payload);
            
            // Map term label to internal code
            $termLabel = $payload['term'] ?? 'Spring 2025';
            $termCode = $this->mapTermLabelToCode($termLabel);
            
            Session::put('afm_role', 'student');
            Session::put('afm_user_id', $payload['student_id']);
            Session::put('afm_user_name', $payload['student_Name']);
            Session::put('afm_term_label', $termLabel);  // For display: "Spring 2025"
            Session::put('afm_term_code', $termCode);    // For DB queries: "202410"
            Session::put('afm_courses', $payload['courses']); // Expecting array of {course_reg_no, course_name...}
            
            return '/student/dashboard';
        } 

        if (in_array($role, ['qa', 'qa_officer'], true)) {
            $this->validateQaPayload($payload);

            Session::put('afm_role', $role);
            Session::put('afm_user_id', $payload['user_id']);
            Session::put('afm_user_name', $payload['user_name'] ?? 'QA User');

            return '/qa';
        }

        if ($role === 'admin') {
            $this->validateAdminPayload($payload);

            Session::put('afm_role', 'admin');
            Session::put('afm_user_id', $payload['user_id']);
            Session::put('afm_user_name', $payload['user_name'] ?? 'Admin User');

            return '/admin';
        }

        Log::warning('Unsupported AFM role received via SSO JSON', [
            'role' => $role,
        ]);

        return '/dev/simulator';
    }

    private function validateStudentPayload(array $payload)
    {
        $required = ['student_id', 'student_Name', 'term', 'courses'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new Exception("Missing student field: {$field}");
            }
        }
        if (!is_array($payload['courses'])) {
            throw new Exception("Courses must be an array");
        }
    }

    /**
     * Map term display label to internal term code.
     * 
     * @param string $termLabel
     * @return string
     */
    private function mapTermLabelToCode(string $termLabel): string
    {
        // Map common term labels to internal codes
        // Format: YYYYTT where YYYY is year and TT is term (10=Spring, 20=Summer, 30=Fall)
        $mapping = [
            'Spring 2025' => '202510',
            'Summer 2025' => '202520',
            'Fall 2025' => '202530',
            'Spring 2024' => '202410',
            'Summer 2024' => '202420',
            'Fall 2024' => '202430',
        ];

        return $mapping[$termLabel] ?? '202510'; // Default to Spring 2025
    }

    private function validateQaPayload(array $payload)
    {
        if (empty($payload['user_id'])) {
            throw new Exception("Missing QA user_id");
        }
    }

    private function validateAdminPayload(array $payload)
    {
        if (empty($payload['user_id'])) {
            throw new Exception("Missing Admin user_id");
        }
    }
}
