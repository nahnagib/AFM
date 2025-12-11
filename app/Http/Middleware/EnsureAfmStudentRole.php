<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureAfmStudentRole
{
    /**
     * Ensure the current session belongs to an AFM student.
     */
    public function handle($request, Closure $next): Response
    {
        $role = Session::get('afm_role');

        if ($role === 'student') {
            return $next($request);
        }

        return redirect('/dev/simulator')
            ->with('error', 'Student access requires a valid AFM JSON login.');
    }
}
