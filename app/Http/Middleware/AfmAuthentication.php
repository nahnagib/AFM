<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TokenService;
use Illuminate\Support\Facades\Session;

class AfmAuthentication
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check Standard Auth (QA/Admin local login)
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            view()->share('afmUser', [
                'name' => $user->name,
                'id' => $user->id,
                'role' => $user->role,
            ]);
            // Mock token object for downstream controllers if needed, or controllers should check both
            // For now, QA controllers don't use $request->afm_token heavily, they rely on DB.
            // But RoleMiddleware needs to check role.
            $request->attributes->set('afm_role', $user->role);
            
            return $next($request);
        }

        // 2. Check SSO Token (Students)
        $tokenId = Session::get('afm_token_id');

        if (!$tokenId) {
            // If in simulated mode, redirect to sim portal, otherwise 401 or redirect to real SIS
            if (config('afm_sso.integration_mode') === 'simulated') {
                return redirect('/sim-sis');
            }
            return redirect('/login'); // Redirect to local login if not simulated
        }

        $token = $this->tokenService->getToken($tokenId);

        if (!$token || $token->isExpired()) {
            Session::forget('afm_token_id');
            if (config('afm_sso.integration_mode') === 'simulated') {
                return redirect('/sim-sis')->with('error', 'Session expired');
            }
            return redirect('/login')->withErrors(['email' => 'Session expired']);
        }

        // Share token with request
        // Share token with request attributes
        $request->attributes->set('afm_token', $token);
        $request->attributes->set('afm_role', $token->role);
        $request->attributes->set('afm_user_id', $token->sis_student_id);
        
        // Build user display name based on role
        if ($token->role === 'student') {
            $userName = 'Student ' . $token->sis_student_id;
        } elseif ($token->role === 'qa' || $token->role === 'qa_officer') {
            $userName = 'QA Officer';
        } else {
            $userName = 'User ' . ($token->sis_student_id ?? 'Unknown');
        }
        
        // Share with views
        view()->share('afmUser', [
            'name' => $userName,
            'id' => $token->sis_student_id,
            'role' => $token->role,
        ]);

        return $next($request);
    }
}
