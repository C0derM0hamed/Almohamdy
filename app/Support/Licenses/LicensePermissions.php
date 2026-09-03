<?php

namespace App\Support\Licenses;

use App\Services\Auth\PermissionService;

final class LicensePermissions
{
    public const VIEW = 'licenses.view';

    public const CREATE = 'licenses.create';

    public const PROCESS = 'licenses.process';

    public const EXPORT = 'licenses.export';

    public const ADMIN = 'licenses_admin';

    public const FINANCE = 'licenses_finance';

    public static function isAdministrator(?PermissionService $permissions = null): bool
    {
        $permissions ??= app(PermissionService::class);

        return $permissions->isAdmin() || $permissions->can(self::ADMIN);
    }

    public static function isFinance(?PermissionService $permissions = null): bool
    {
        $permissions ??= app(PermissionService::class);

        return $permissions->isAdmin() || $permissions->can(self::FINANCE);
    }

    public static function canProcess(?PermissionService $permissions = null): bool
    {
        $permissions ??= app(PermissionService::class);

        return self::isAdministrator($permissions) || $permissions->can(self::PROCESS);
    }
}
