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

        // Redirect based on role
        if ($token->role === 'student') {
            return redirect('/student/dashboard');
        } elseif ($token->role === 'qa_officer') {
            return redirect('/qa');
        }

        return redirect('/');
    }
}
