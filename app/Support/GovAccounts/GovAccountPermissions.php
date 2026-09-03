<?php

namespace App\Support\GovAccounts;

use App\Services\Auth\PermissionService;

final class GovAccountPermissions
{
    public const VIEW = 'gov_accounts.view';

    public const REQUEST = 'gov_accounts.request';

    public const PROCESS = 'gov_accounts.process';

    public const HR = 'gov_accounts.hr';

    public const EXPORT = 'gov_accounts.export';

    public const ADMIN = 'gov_accounts_admin';

    public static function isAdministrator(?PermissionService $permissions = null): bool
    {
        $permissions ??= app(PermissionService::class);

        return $permissions->isAdmin() || $permissions->can(self::ADMIN);
    }
}
