<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SisStudent;
use App\Models\SisEnrollment;
use App\Services\SsoTokenIntakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SimSisController extends Controller
{
    public function index()
    {
        $students = SisStudent::all();
        return view('sim-sis.portal', ['students' => $students]);
    }

    public function launch(Request $request, SsoTokenIntakeService $intake)
    {
        // Use where()->firstOrFail() because student_id is a string column, not the PK
        $student = SisStudent::where('student_id', $request->input('student_id'))->firstOrFail();

        $courses = SisEnrollment::with('course')
            ->where('student_id', $student->student_id)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'course_reg_no' => $enrollment->course_reg_no,
                    'course_code'   => $enrollment->course->code,
                    'course_name'   => $enrollment->course->name,
                    'term_code'     => $enrollment->term_code,
                ];
            })
            ->values()
            ->toArray();

        $payload = [
            'iss'            => config('afm_sso.iss'),
            'aud'            => config('afm_sso.aud'),
            'v'              => config('afm_sso.version'),
            'request_id'     => (string) Str::uuid(),
            'role'           => 'student',
            'student_id'     => (string) $student->student_id,
            'student_name'   => $student->full_name,
            'student_number' => $student->student_id, // SIS ID used as student number
            'courses'        => $courses,
            'issued_at'      => now()->timestamp,
            'expires_at'     => now()->addMinutes(5)->timestamp,
            'nonce'          => Str::random(32),
            'sig_alg'        => 'HMAC-SHA256',
        ];

        $secret = config('afm_sso.shared_secret');

        if (empty($secret)) {
            throw new \RuntimeException('afm_sso.shared_secret is not configured.');
        }

        $canonicalJson = \App\Support\AfmJsonCanonicalizer::canonicalize($payload);
        $signature = hash_hmac('sha256', $canonicalJson, $secret);
        $payload['signature'] = $signature;

        // Log JSON payload to terminal
        \Illuminate\Support\Facades\Log::info('SimSis STUDENT SSO Payload', ['payload' => $payload]);
        \Illuminate\Support\Facades\Log::info('SimSis STUDENT SSO JSON: ' . json_encode($payload, JSON_PRETTY_PRINT));

        // Call the intake service directly
        // We catch exceptions to provide a clearer error message for the simulator
        try {
            $result = $intake->handle($payload, $request);
        } catch (\Exception $e) {
            throw new \RuntimeException('SSO Intake Failed: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $redirectUrl = is_array($result)
            ? ($result['redirect_to'] ?? null)
            : ($result->redirectTo ?? null);

        if (! $redirectUrl) {
            throw new \RuntimeException('SsoTokenIntakeService did not return a redirect URL.');
        }

        return redirect()->away($redirectUrl);
    }

    public function launchQa(Request $request, SsoTokenIntakeService $intake)
    {
        // Simulated QA user
        $qaUserId = 'U9021';

        $payload = [
            'iss'        => config('afm_sso.iss'),
            'aud'        => config('afm_sso.aud'),
            'v'          => config('afm_sso.version'),
            'request_id' => (string) Str::uuid(),
            'role'       => 'qa_officer',
            'user_id'    => $qaUserId,
            'user_name'  => 'QA Officer Demo',
            'issued_at'  => now()->timestamp,
            'expires_at' => now()->addMinutes(5)->timestamp,
            'nonce'      => Str::random(32),
            'sig_alg'    => 'HMAC-SHA256',
        ];

        $secret = config('afm_sso.shared_secret');

        if (empty($secret)) {
            throw new \RuntimeException('afm_sso.shared_secret is not configured.');
        }

        $canonicalJson = \App\Support\AfmJsonCanonicalizer::canonicalize($payload);
        $signature = hash_hmac('sha256', $canonicalJson, $secret);
        $payload['signature'] = $signature;

        // Log JSON payload to terminal
        \Illuminate\Support\Facades\Log::info('SimSis QA SSO Payload', ['payload' => $payload]);
        \Illuminate\Support\Facades\Log::info('SimSis QA SSO JSON: ' . json_encode($payload, JSON_PRETTY_PRINT));

        // Call the intake service directly
        try {
            $result = $intake->handle($payload, $request);
        } catch (\Exception $e) {
            throw new \RuntimeException('QA SSO Intake Failed: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $redirectUrl = is_array($result)
            ? ($result['redirect_to'] ?? null)
            : ($result->redirectTo ?? null);

        if (! $redirectUrl) {
            throw new \RuntimeException('SsoTokenIntakeService did not return a redirect URL.');
        }

        return redirect()->away($redirectUrl);
    }
}
