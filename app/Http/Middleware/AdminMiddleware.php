<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('is_admin') !== true) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your administrator session has expired. Please sign in again.',
                ], 401);
            }

            return redirect()->route('admin.login')->with('error', 'Please sign in with an administrator account.');
        }

        return $next($request);
    }
}
