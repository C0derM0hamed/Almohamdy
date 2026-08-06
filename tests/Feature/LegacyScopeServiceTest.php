<?php

namespace Tests\Feature;

use App\Services\Auth\PermissionService;
use App\Services\Auth\LegacyScopeService;
use App\Support\CorporateCommunications\CorporateCommunicationPermissions;
use App\Support\Training\TrainingPermissions;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Tests\TestCase;

class LegacyScopeServiceTest extends TestCase
{
    public function test_legacy_employee_and_outgoing_scopes_follow_old_branch_shell(): void
    {
        session([
            'hr_user_level' => 2,
            'hr_branch_id' => 1,
            'companies_groups_id' => 1,
        ]);

        $scopes = app(LegacyScopeService::class);

        $this->assertTrue($scopes->allows(LegacyScopeService::EMPLOYEE_SERVICES));
        $this->assertTrue($scopes->allows(LegacyScopeService::CORPORATE_OUTGOING));
        $this->assertTrue($scopes->allows(LegacyScopeService::TECHNICAL_FAILURE));
        $this->assertTrue($scopes->allows(LegacyScopeService::SADQ));
    }

    public function test_legacy_branch_two_keeps_technical_and_sadq_hidden(): void
    {
        session([
            'hr_user_level' => 4,
            'hr_branch_id' => 2,
            'companies_groups_id' => 1,
        ]);

        $scopes = app(LegacyScopeService::class);

        $this->assertTrue($scopes->allows(LegacyScopeService::EMPLOYEE_SERVICES));
        $this->assertTrue($scopes->allows(LegacyScopeService::CORPORATE_OUTGOING));
        $this->assertFalse($scopes->allows(LegacyScopeService::TECHNICAL_FAILURE));
        $this->assertFalse($scopes->allows(LegacyScopeService::SADQ));
    }

    public function test_employee_scope_does_not_cross_company_boundary(): void
    {
        session([
            'hr_user_level' => 2,
            'hr_branch_id' => 1,
            'companies_groups_id' => 2,
        ]);

        $this->assertFalse(app(LegacyScopeService::class)->allows(LegacyScopeService::EMPLOYEE_SERVICES));
    }

    public function test_legacy_permission_grants_are_shared_by_sidebar_and_route_authorization(): void
    {
        session([
            'hr_user_id' => 1,
            'hr_user_level' => 2,
            'hr_branch_id' => 1,
            'companies_groups_id' => 1,
            'groupid' => 0,
            'hm_permissions' => [],
        ]);

        $permissions = app(PermissionService::class);

        $this->assertTrue($permissions->can(WorkAbsenceNotificationPermissions::VIEW));
        $this->assertTrue($permissions->can(TrainingPermissions::MANAGEMENT));
        $this->assertTrue($permissions->can(TrainingPermissions::COORDINATION));
        $this->assertTrue($permissions->can(CorporateCommunicationPermissions::OUTGOING_CORRESPONDENCE));
        $this->assertTrue($permissions->can('technical_failure_notice'));

        session(['hr_branch_id' => 2]);

        $this->assertFalse($permissions->can('technical_failure_notice'));
    }
}
