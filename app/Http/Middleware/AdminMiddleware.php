<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please sign in to access the administrator portal.');
        }

        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Administrator privileges required.');
        }

        if (auth()->user()->status === 'banned' || auth()->user()->status === 'suspended') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your administrator account has been restricted.');
        }

        return $next($request);
    }
}
