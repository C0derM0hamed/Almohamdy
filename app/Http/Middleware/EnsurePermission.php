<?php

namespace App\Http\Middleware;

use App\Services\Auth\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $this->permissions->can($permission)) {
            $this->permissions->authorize($permission);
        }

        return $next($request);
    }
}
