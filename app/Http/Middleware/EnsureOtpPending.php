<?php

namespace App\Http\Middleware;

use App\Services\Dashboard\NavigationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpPending
{
    public function __construct(
        private readonly NavigationService $navigation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('hr_user_id')) {
            return redirect()->route($this->navigation->homeRouteName());
        }

        if (! $request->session()->get('step1')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
