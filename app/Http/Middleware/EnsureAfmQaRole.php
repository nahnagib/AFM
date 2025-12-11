<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureAfmQaRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = Session::get('afm_role');

        if ($role === 'qa') {
            return $next($request);
        }

        // Handle variants if necessary (e.g. 'qa_officer') or strictly 'qa' as requested
        // The previous code had 'qa' and 'qa_officer'. The JSON simulator uses 'qa'.
        // Let's allow both to be safe, but primarily 'qa'.
        if ($role === 'qa_officer') {
             return $next($request);
        }

        // No valid QA session → send back to simulator
        return redirect()->route('dev.simulator')
            ->with('error', 'QA access requires a valid AFM JSON login.');
    }
}
