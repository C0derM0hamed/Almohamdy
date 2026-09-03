<?php

namespace App\Support\EmergencyReception;

final class EmergencyReceptionAccess
{
    public static function authorize(bool $allowAnyBranch = false): void
    {
        $level = (int) session('hr_user_level', 0);
        $branch = (int) session('hr_branch_id', 0);

        abort_unless(
            // Level 3 is the top-level administrator and is allowed to audit
            // and operate the legacy emergency workflows from any branch.
            // The previous guard excluded it, which made the sidebar link
            // visible to administrators while every page returned 403.
            ($allowAnyBranch || $level === 3 || $branch === 1)
            && (int) session('companies_groups_id', 0) > 0
            && ($level === 3 || in_array($level, [1, 2, 4], true)),
            403
        );
    }
}
