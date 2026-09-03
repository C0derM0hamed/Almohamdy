<?php

namespace App\Services\GovAccounts;

use App\Models\User;

class NullEmployeeStatusProvider implements EmployeeStatusProviderInterface
{
    public function isActive(User $employee): ?bool
    {
        return null;
    }
}
