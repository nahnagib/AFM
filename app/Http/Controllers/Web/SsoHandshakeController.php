<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SsoHandshakeController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function handshake(Request $request, $tokenId)
    {
        $token = $this->tokenService->getToken($tokenId);

        if (!$token || !$token->isValid()) {
            abort(401, 'Invalid or expired session token');
        }

        // Mark consumed
        $this->tokenService->consumeToken($token);

        // Establish Session
        Session::put('afm_token_id', $token->id);
        Session::put('afm_role', $token->role);

        // Store student-specific data in session for student role
        if ($token->role === 'student') {
            Session::put('afm_user_id', $token->sis_student_id);
            Session::put('afm_user_name', $token->student_name ?? 'Student');
            Session::put('afm_courses', $token->courses_json ?? []);
            
            // Extract term code from courses (assuming all courses have same term)
            $termCode = 'Spring 2025'; // Default
            if (!empty($token->courses_json) && isset($token->courses_json[0]['term_code'])) {
                $termCode = $token->courses_json[0]['term_code'];
            }
            Session::put('afm_term_code', $termCode);
        }

        // Redirect based on role
        if ($token->role === 'student') {
            return redirect('/student/dashboard');
        } elseif ($token->role === 'qa_officer') {
            return redirect('/qa');
        }

        return redirect('/');
    }
}
