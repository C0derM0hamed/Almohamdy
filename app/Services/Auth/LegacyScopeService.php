<?php

namespace App\Services\Auth;

final class LegacyScopeService
{
    public const EMPLOYEE_SERVICES = 'employee-services';

    public const CORPORATE_OUTGOING = 'corporate-outgoing';

    public const TECHNICAL_FAILURE = 'technical-failure';

    public const SADQ = 'sadq';

    public function allows(string $scope): bool
    {
        $level = (int) session('hr_user_level', 0);
        $branch = (int) session('hr_branch_id', 0);
        $company = (int) session('companies_groups_id', 0);

        if ($level === 3) {
            return true;
        }

        return match ($scope) {
            self::EMPLOYEE_SERVICES => in_array($level, [1, 2, 4], true)
                && in_array($company, [1, 3], true),
            self::CORPORATE_OUTGOING => in_array($level, [1, 2, 4], true),
            self::TECHNICAL_FAILURE => in_array($level, [1, 2, 4], true)
                && in_array($branch, [1, 5, 7, 8], true),
            self::SADQ => in_array($level, [1, 2, 4], true)
                && in_array($branch, [1, 5, 8], true),
            default => false,
        };
    }

    public function allowsPermission(string $permission): bool
    {
        return match ($permission) {
            'absence_notification_service',
            'work_absence_notification.view',
            'work_absence_notification.process',
            'work_absence_notification.activate',
            'work_absence_notification.export',
            'view training_confirmation',
            'view training_confirmation_coordinator' => $this->allows(self::EMPLOYEE_SERVICES),
            'corporate_communications_outgoing_letters' => $this->allows(self::CORPORATE_OUTGOING),
            'technical_failure_notice' => $this->allows(self::TECHNICAL_FAILURE),
            default => false,
        };
    }
}
