<?php

namespace App\Http\Requests\WorkAbsenceNotification\Concerns;

use App\Services\Auth\PermissionService;

trait AuthorizesWorkAbsenceNotification
{
    protected function authorizePermission(string $permission): bool
    {
        app(PermissionService::class)->authorize($permission);

        return true;
    }
}
