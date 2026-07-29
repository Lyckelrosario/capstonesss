<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            return redirect()->route('login')->with('error', 'Please sign in first.');
        }

        abort_unless($request->user()->role === $role, 403);

        return $next($request);
    }
}
