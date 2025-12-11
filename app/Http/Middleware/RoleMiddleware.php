<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $role = $request->attributes->get('afm_role');

        if (!$role) {
            if (app()->environment('local')) {
                return redirect()->route('dev.simulator')->with('error', 'Authentication required. Please login via Simulator.');
            }
            abort(401, 'Unauthenticated');
        }

        if (!in_array($role, $roles)) {
            if (app()->environment('local')) {
                return redirect()->route('dev.simulator')->with('error', 'Unauthorized access for role: ' . $role);
            }
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
