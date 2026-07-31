<?php

namespace App\Support\EmployeeLeave;

final class EmployeeLeavePermissions
{
    public const VIEW = 'employee_leave.view';

    public const APPLY = 'employee_leave.apply';

    public const BRANCH_PROCESS = 'employee_leave.branch_process';

    public const HR_PROCESS = 'employee_leave.hr_process';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::APPLY,
            self::BRANCH_PROCESS,
            self::HR_PROCESS,
        ];
    }
}
