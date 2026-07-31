<?php

namespace App\Http\Requests\EmployeeLeave\Concerns;

use App\Services\Auth\PermissionService;

trait AuthorizesEmployeeLeave
{
    protected function authorizePermission(string $permission): bool
    {
        app(PermissionService::class)->authorize($permission);

        return true;
    }
}
