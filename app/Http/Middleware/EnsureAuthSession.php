<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('hr_user_id')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
