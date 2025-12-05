<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AfmAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user has valid AFM session token
        if (!session()->has('afm_token_id') || !session()->has('afm_role')) {
            return redirect('/')->with('error', 'Please authenticate via SSO to access AFM.');
        }

        return $next($request);
    }
}
