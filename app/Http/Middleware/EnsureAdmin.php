<?php

namespace App\Http\Middleware;

use App\Services\Auth\PermissionService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->permissions->isAdmin()) {
            throw new AuthorizationException(__('system_administration.errors.unauthorized'));
        }

        return $next($request);
    }
}
