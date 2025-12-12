<?php

namespace App\Http\Controllers;

use App\Services\SsoJsonIntakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DevSimulatorController extends Controller
{
    public function index()
    {
        // -------------------------------------------------------------------------
        // AFM PROTOTYPE SIMULATOR
        // -------------------------------------------------------------------------
        // This controller simulates the role of the University Student Information System (SIS).
        // It generates a JSON payload, canonicalizes it, signs it with a shared secret,
        // and sends it to AFM's intake endpoint.
        
        $secret = config('afm_sso.shared_secret');
        
        // 7 Student Payloads
        $students = [
            [
                'id' => '4401', 'name' => 'Nahla Burweiss', 
                'courses' => [
                    ['course_reg_no' => 'SE401-Spring2025', 'course_name' => 'Software Engineering Project'],
                    ['course_reg_no' => 'CS302-Spring2025', 'course_name' => 'Database Systems'],
                ]
            ],
            [
                'id' => '3675', 'name' => 'Ali Ahmed',
                'courses' => [
                     ['course_reg_no' => 'IT210-Spring2025', 'course_name' => 'Computer Networks'],
                ]
            ],
             [
                'id' => '3001', 'name' => 'Sara Mohammed',
                'courses' => [
                     ['course_reg_no' => 'MA201-Spring2025', 'course_name' => 'Discrete Mathematics'],
                ]
            ],
            [
                'id' => '3002', 'name' => 'Khaled Salem', 'courses' => [['course_reg_no' => 'SE401-Spring2025', 'course_name' => 'Software Engineering Project']]
            ],
            [
                'id' => '3003', 'name' => 'Fatima Noor', 'courses' => [['course_reg_no' => 'CS302-Spring2025', 'course_name' => 'Database Systems']]
            ],
            [
                'id' => '3004', 'name' => 'Omar Libi', 'courses' => [['course_reg_no' => 'IT210-Spring2025', 'course_name' => 'Computer Networks']]
            ],
            [
                'id' => '3005', 'name' => 'Layla Benali', 'courses' => [['course_reg_no' => 'CS302-Spring2025', 'course_name' => 'Database Systems']]
            ],
        ];

        $payloads = [];

        foreach ($students as $s) {
            $rawPayload = [
                "iss"          => "LIMU-SIS",
                "aud"          => "AFM",
                "v"            => "1",
                "request_id"   => Str::uuid()->toString(),
                "role"         => "student",
                "student_id"   => $s['id'],
                "student_Name" => $s['name'],
                "term"         => "Spring 2025",
                "courses"      => $s['courses'],
                // Hardcoded times for stable demo or dynamic? Dynamic is better for testing expiry
                "issued_at"    => now()->toIso8601String(),
                "expires_at"   => now()->addMinutes(15)->toIso8601String(),
                "nonce"        => Str::random(12),
                "sig_alg"      => "HMAC-SHA256"
            ];

            // 1. Canonicalize (Sort keys, remove whitespace)
            $canonicalString = \App\Support\AfmJsonCanonicalizer::canonicalize($rawPayload);

            // 2. Sign (HMAC-SHA256)
            $signature = hash_hmac('sha256', $canonicalString, $secret);

            // 3. Attach Signature
            $rawPayload['signature'] = $signature;
            $payloads[] = $rawPayload;
        }

        // QA Payload
        $qaPayload = [
            "iss"        => "LIMU-SIS",
            "aud"        => "AFM",
            "v"          => "1",
            "request_id" => Str::uuid()->toString(),
            "role"       => "qa_officer", // Must match 'allowed_roles' config
            "user_id"    => "U9021",
            "user_name"  => "Dr. Ahmed QA",
            "issued_at"  => now()->toIso8601String(),
            "expires_at" => now()->addMinutes(15)->toIso8601String(),
            "nonce"      => Str::random(12),
            "sig_alg"    => "HMAC-SHA256"
        ];
        
        $qaCanonical = \App\Support\AfmJsonCanonicalizer::canonicalize($qaPayload);
        $qaPayload['signature'] = hash_hmac('sha256', $qaCanonical, $secret);
        $payloads[] = $qaPayload;

        /*
         * EDUCATIONAL EXAMPLE:
         * ---------------------------------------------------------
         * If the payload is: {"aud":"AFM","iss":"LIMU-SIS"}
         * And the secret is: "secret"
         * 
         * 1. Canonical String: {"aud":"AFM","iss":"LIMU-SIS"}
         * 2. HMAC-SHA256: 7f63b0... (hex)
         * 
         * This ensures that even if 'iss' came first in the source array,
         * the signature is always computed against the sorted keys.
         */

        return view('dev.simulator', ['payloads' => $payloads]);
    }

    public function login(Request $request, SsoJsonIntakeService $intake)
    {
        try {
            $jsonString = $request->input('payload', $request->input('json_payload'));

            if (!$jsonString) {
                throw new \Exception('Missing JSON payload');
            }

            $payload = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
                throw new \Exception('Invalid JSON format');
            }

            // Log
            Log::info('AFM DEV JSON LOGIN', [
                'role' => $payload['role'] ?? null,
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);

            // Handle
            $redirectPath = $intake->handle($payload);

            return redirect($redirectPath ?? '/afm');

        } catch (\Exception $e) {
            return redirect()->route('dev.simulator')
                ->with('error', 'Login Failed: ' . $e->getMessage());
        }
    }
}
