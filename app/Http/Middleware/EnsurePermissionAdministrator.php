<?php

namespace App\Http\Middleware;

use App\Services\Auth\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermissionAdministrator
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->permissions->canManageUsers()) {
            abort(403, __('system_administration.users.errors.unauthorized'));
        }

        return $next($request);
    }
}
