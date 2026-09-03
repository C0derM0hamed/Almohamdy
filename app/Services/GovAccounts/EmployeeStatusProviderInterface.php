<?php

namespace App\Services\GovAccounts;

use App\Models\User;

interface EmployeeStatusProviderInterface
{
    /** Return true/false when a real source knows the status, or null when it does not. */
    public function isActive(User $employee): ?bool;
}
