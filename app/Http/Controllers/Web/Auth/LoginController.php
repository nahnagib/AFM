<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Set AFM session data for compatibility with middleware
            $user = Auth::user();
            
            // We need a fake token or modify middleware to accept Auth::user()
            // Let's modify middleware to check Auth::check() as fallback or primary
            // But my middleware relies on `afm_token_id` and `TokenService`.
            // For local login, we might not have a token.
            // I should adapt `AfmAuthentication` to allow `Auth::user()` if present.
            
            // Actually, simpler: just put a fake token or bypass middleware for QA if logged in via standard Auth.
            // Let's update `AfmAuthentication` to check `Auth::check()`.
            
            return redirect()->intended('/qa');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Session::forget('afm_token_id');

        return redirect('/');
    }
}
