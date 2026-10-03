<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $isStaff = $request->session()->get('is_staff') === true;

        if (!$isStaff) {
            return redirect()->route('staff.login')->with('error', 'Please sign in with a staff or administrator account.');
        }

        return $next($request)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
