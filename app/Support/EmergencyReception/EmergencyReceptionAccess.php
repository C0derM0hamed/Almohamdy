<?php

namespace App\Support\EmergencyReception;

final class EmergencyReceptionAccess
{
    public static function authorize(): void
    {
        abort_unless(
            (int) session('hr_branch_id', 0) === 1
            && (int) session('companies_groups_id', 0) > 0
            && in_array((int) session('hr_user_level', 0), [1, 2, 4], true),
            403
        );
    }
}
